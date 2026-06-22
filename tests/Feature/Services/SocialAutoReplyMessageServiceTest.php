<?php

namespace Tests\Feature\Services;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialAutoReplyMessageService;
use App\Services\SocialCrmSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SocialAutoReplyMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://odon.test',
            'services.whatsapp.business_phone' => '593991112233',
            'services.ai.provider' => 'openai',
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.api_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        URL::forceRootUrl('https://odon.test');
    }

    public function test_generates_template_message_when_ai_is_disabled(): void
    {
        $this->setting('social_auto_reply_use_ai', false, 'boolean');
        $this->setting('social_auto_reply_company_name', 'Clínica Sonríe');
        $comment = $this->socialComment();

        $response = app(SocialAutoReplyMessageService::class)->generate($comment);

        $this->assertSame('template', $response['source']);
        $this->assertFalse($response['requires_human_review']);
        $this->assertStringContainsString('👋 Te saluda Clínica Sonríe', $response['message']);
        $this->assertStringContainsString('https://odon.test/v/', $response['message']);
        $this->assertStringContainsString($comment->refresh()->tracking_token, $response['variables']['smart_link']);
    }

    public function test_generates_safe_ai_message_with_smart_link(): void
    {
        $comment = $this->socialComment();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => "👋 Te saluda Clínica Dental\n\nHola, claro. Te compartimos la información inicial y próximos pasos aquí: https://odon.test/v/DNT-ABCDE",
                                'requires_human_review' => false,
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ]),
        ]);

        $comment->update(['tracking_token' => 'DNT-ABCDE']);

        $response = app(SocialAutoReplyMessageService::class)->generate($comment->refresh());

        $this->assertSame('ai', $response['source']);
        $this->assertFalse($response['requires_human_review']);
        $this->assertSame(
            "👋 Te saluda Clínica Dental\n\nHola, claro. Te compartimos la información inicial y próximos pasos aquí: https://odon.test/v/DNT-ABCDE",
            $response['message'],
        );
    }

    public function test_uses_fallback_when_ai_message_does_not_include_link(): void
    {
        $comment = $this->socialComment();

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => "👋 Te saluda Clínica Dental\n\nHola, claro. Te ayudamos por interno.",
                                'requires_human_review' => false,
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ]),
        ]);

        $response = app(SocialAutoReplyMessageService::class)->generate($comment);

        $this->assertSame('fallback', $response['source']);
        $this->assertStringContainsString('https://odon.test/v/', $response['message']);
    }

    public function test_marks_human_review_when_ai_requests_it(): void
    {
        $comment = $this->socialComment(['comment_text' => 'Tengo dolor fuerte y sangrado']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => 'HUMAN_REVIEW_REQUIRED',
                                'requires_human_review' => true,
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $response = app(SocialAutoReplyMessageService::class)->generate($comment);

        $this->assertSame('ai', $response['source']);
        $this->assertTrue($response['requires_human_review']);
        $this->assertNull($response['message']);
    }

    public function test_uses_whatsapp_link_when_smart_link_setting_is_disabled(): void
    {
        $this->setting('social_auto_reply_use_ai', false, 'boolean');
        $this->setting('social_auto_reply_use_smart_link', false, 'boolean');
        $this->setting('social_auto_reply_template', 'Hola, te ayudamos aquí: {whatsapp_link}');
        $comment = $this->socialComment();

        $response = app(SocialAutoReplyMessageService::class)->generate($comment);

        $this->assertStringContainsString('https://wa.me/593991112233', $response['message']);
        $this->assertStringNotContainsString('https://odon.test/v/', $response['message']);
    }

    private function setting(string $key, mixed $value, string $type = 'string'): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key],
            [
                'setting_group' => 'auto_reply',
                'label' => $key,
                'value_type' => $type,
                'value' => $value,
                'is_active' => true,
            ],
        );

        app(SocialCrmSettingsService::class)->clearCache();
    }

    private function socialComment(array $overrides = []): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create(array_merge([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Info por favor',
        ], $overrides));
    }
}
