<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialReputationRisk;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialAutoReplyService;
use App\Services\SocialCrmSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SocialAutoReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://odon.test',
            'services.whatsapp.business_phone' => '593991112233',
            'services.meta.api_url' => 'https://graph.facebook.com/v25.0',
        ]);

        URL::forceRootUrl('https://odon.test');
        $this->setting('social_auto_reply_use_ai', false, 'boolean');
    }

    public function test_skips_when_auto_reply_is_disabled(): void
    {
        $this->setting('social_auto_reply_enabled', false, 'boolean');
        $comment = $this->socialComment();

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('auto_reply_disabled', $result['reason']);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySkipped->value,
        ]);
    }

    public function test_skips_when_classification_is_not_allowed(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment([
            'classification' => SocialCommentClassification::Normal,
        ]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('classification_not_allowed', $result['reason']);
    }

    public function test_skips_when_human_review_is_required(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment(['requires_human_review' => true]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('human_review_required', $result['reason']);
    }

    public function test_skips_high_reputation_risk(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment(['reputation_risk' => SocialReputationRisk::High]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('reputation_risk', $result['reason']);
    }

    public function test_dry_run_generates_message_without_publishing(): void
    {
        $this->enableDryRun();
        Http::fake();
        $comment = $this->socialComment();

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $comment->refresh();

        $this->assertSame('generated', $result['status']);
        $this->assertFalse($result['published']);
        $this->assertTrue($result['dry_run']);
        $this->assertNull($comment->auto_replied_at);
        $this->assertNull($comment->auto_reply_external_id);
        $this->assertSame(0, $comment->auto_reply_attempts);
        $this->assertStringContainsString('https://odon.test/v/', $comment->auto_reply_message);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplyGenerated->value,
        ]);
        Http::assertNothingSent();
    }

    public function test_publishes_reply_when_dry_run_is_disabled(): void
    {
        $this->enablePublishing();
        $comment = $this->socialComment([], SocialPlatform::Instagram, 'ig_comment_1');

        Http::fake([
            'https://graph.facebook.com/v25.0/ig_comment_1/replies' => Http::response(['id' => 'ig_reply_1']),
        ]);

        $result = app(SocialAutoReplyService::class)->handle($comment);
        $comment->refresh();

        $this->assertSame('sent', $result['status']);
        $this->assertTrue($result['published']);
        $this->assertSame('ig_reply_1', $comment->auto_reply_external_id);
        $this->assertSame(1, $comment->auto_reply_attempts);
        $this->assertSame(SocialCommentStatus::Responded, $comment->status);
        $this->assertNotNull($comment->auto_replied_at);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplySent->value,
        ]);
    }

    public function test_records_failure_when_meta_publish_fails(): void
    {
        $this->enablePublishing();
        $comment = $this->socialComment([], SocialPlatform::Facebook, 'fb_comment_1');

        Http::fake([
            'https://graph.facebook.com/v25.0/fb_comment_1/comments' => Http::response([
                'error' => ['message' => 'Invalid token', 'code' => 190],
            ], 400),
        ]);

        $result = app(SocialAutoReplyService::class)->handle($comment);
        $comment->refresh();

        $this->assertSame('failed', $result['status']);
        $this->assertFalse($result['published']);
        $this->assertSame(1, $comment->auto_reply_attempts);
        $this->assertNotNull($comment->auto_reply_error);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplyFailed->value,
        ]);
    }

    public function test_skips_already_replied_comments(): void
    {
        $this->enablePublishing();
        $comment = $this->socialComment(['auto_replied_at' => now()]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('already_replied', $result['reason']);
    }

    public function test_skips_sensitive_text(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment(['comment_text' => 'Tengo dolor y sangrado']);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('sensitive_text', $result['reason']);
    }

    public function test_skips_when_max_attempts_reached(): void
    {
        $this->enablePublishing();
        $this->setting('social_auto_reply_max_attempts', 2, 'integer');
        $comment = $this->socialComment(['auto_reply_attempts' => 2]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('max_attempts_reached', $result['reason']);
        $this->assertSame(2, $comment->fresh()->auto_reply_attempts);
    }

    public function test_skips_disallowed_comment_status(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment(['status' => SocialCommentStatus::Ignored]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('status_not_allowed', $result['reason']);
    }

    public function test_skips_when_auto_reply_sent_action_exists(): void
    {
        $this->enableDryRun();
        $comment = $this->socialComment();
        $comment->actions()->create([
            'action' => SocialCommentActionType::AutoReplySent,
            'notes' => 'Respuesta previa.',
        ]);

        $result = app(SocialAutoReplyService::class)->handle($comment);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('already_replied', $result['reason']);
    }

    private function enableDryRun(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        $this->setting('social_auto_reply_dry_run', true, 'boolean');
    }

    private function enablePublishing(): void
    {
        $this->setting('social_auto_reply_enabled', true, 'boolean');
        $this->setting('social_auto_reply_dry_run', false, 'boolean');
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

    private function socialComment(array $overrides = [], SocialPlatform $platform = SocialPlatform::Instagram, ?string $externalCommentId = null): SocialComment
    {
        $externalCommentId ??= 'comment_'.uniqid();

        $account = SocialAccount::create([
            'platform' => $platform,
            'account_name' => 'Clinica Dental',
            'external_account_id' => $platform === SocialPlatform::Instagram ? 'ig_account_'.uniqid() : 'page_'.uniqid(),
            'page_id' => $platform === SocialPlatform::Facebook ? 'page_1' : null,
            'instagram_business_account_id' => $platform === SocialPlatform::Instagram ? 'ig_1' : null,
            'access_token' => 'page-token',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => $platform,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'platform' => $platform,
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
            'platform' => $platform,
            'external_comment_id' => $externalCommentId,
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Info por favor',
            'classification' => SocialCommentClassification::SalesLead,
            'status' => SocialCommentStatus::Classified,
            'requires_human_review' => false,
            'reputation_risk' => SocialReputationRisk::Low,
        ], $overrides));
    }
}
