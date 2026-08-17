<?php

namespace Tests\Feature\Jobs;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialReputationRisk;
use App\Jobs\SendSocialCommentAutoReply;
use App\Models\Clinic;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialAutoReplyService;
use App\Services\SocialCrmSettingsService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SendSocialCommentAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://odon.test',
            'services.whatsapp.business_phone' => '593991112233',
        ]);

        URL::forceRootUrl('https://odon.test');
    }

    public function test_job_executes_auto_reply_service_for_comment(): void
    {
        $clinic = $this->createClinic();
        $this->enableDryRun($clinic);
        $comment = $this->socialComment($clinic);

        Http::fake();

        (new SendSocialCommentAutoReply($comment->id, $comment->clinic_id))->handle(
            app(SocialAutoReplyService::class),
            app(TenantContext::class),
        );

        $comment->refresh();

        $this->assertNotNull($comment->auto_reply_message);
        $this->assertNull($comment->auto_replied_at);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AutoReplyGenerated->value,
        ]);
        Http::assertNothingSent();
    }

    public function test_job_ignores_missing_comment(): void
    {
        Log::spy();

        (new SendSocialCommentAutoReply(999999))->handle(
            app(SocialAutoReplyService::class),
            app(TenantContext::class),
        );

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'comentario social no encontrado')
                && $context['social_comment_id'] === 999999);
    }

    public function test_job_configuration_uses_single_try_and_timeout(): void
    {
        $job = new SendSocialCommentAutoReply(123);

        $this->assertSame(123, $job->socialCommentId);
        $this->assertSame(1, $job->tries);
        $this->assertSame(30, $job->timeout);
    }

    private function createClinic(): Clinic
    {
        return Clinic::create([
            'name' => 'Test Clinic',
            'slug' => 'test-clinic',
            'subdomain' => 'test-clinic',
            'primary_domain' => 'test-clinic.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function enableDryRun(Clinic $clinic): void
    {
        $this->setting($clinic, 'social_auto_reply_enabled', true, 'boolean');
        $this->setting($clinic, 'social_auto_reply_use_ai', false, 'boolean');
        $this->setting($clinic, 'social_auto_reply_dry_run', true, 'boolean');
    }

    private function setting(Clinic $clinic, string $key, mixed $value, string $type = 'string'): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key, 'clinic_id' => $clinic->id],
            [
                'setting_group' => 'auto_reply',
                'label' => $key,
                'value_type' => $type,
                'value' => $value,
                'is_active' => true,
                'clinic_id' => $clinic->id,
            ],
        );

        app(SocialCrmSettingsService::class)->clearCache();
    }

    private function socialComment(Clinic $clinic): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'instagram_business_account_id' => 'ig_1',
            'access_token' => 'page-token',
            'is_active' => true,
            'clinic_id' => $clinic->id,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
            'clinic_id' => $clinic->id,
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

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Info por favor',
            'classification' => SocialCommentClassification::SalesLead,
            'status' => SocialCommentStatus::Classified,
            'requires_human_review' => false,
            'reputation_risk' => SocialReputationRisk::Low,
            'clinic_id' => $clinic->id,
        ]);
    }
}
