<?php

namespace Tests\Feature\Services;

use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialLeadAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLeadAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_alert_is_idempotent_for_open_alerts(): void
    {
        $comment = $this->socialComment();
        $service = app(SocialLeadAlertService::class);

        $first = $service->createAlert($comment, 'hot_lead_created', 'danger');
        $second = $service->createAlert($comment, 'hot_lead_created', 'danger');

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('social_lead_alerts', 1);
    }

    public function test_scheduled_checks_create_operational_alerts(): void
    {
        $overdue = $this->socialComment([
            'interest_score' => 90,
            'hot_lead_at' => now()->subHours(8),
        ]);
        $pending = $this->socialComment([
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
        ]);
        $followUp = $this->socialComment([
            'interest_score' => 90,
            'hot_lead_at' => now(),
            'follow_up_at' => now()->subMinute(),
        ]);

        app(SocialLeadAlertService::class)->runScheduledChecks();

        $this->assertDatabaseHas('social_lead_alerts', [
            'social_comment_id' => $overdue->id,
            'alert_type' => 'no_contact_overdue',
        ]);
        $this->assertDatabaseHas('social_lead_alerts', [
            'social_comment_id' => $pending->id,
            'alert_type' => 'pending_patient_creation',
        ]);
        $this->assertDatabaseHas('social_lead_alerts', [
            'social_comment_id' => $followUp->id,
            'alert_type' => 'follow_up_due',
        ]);
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
            'comment_text' => 'Quiero informacion',
            'conversion_status' => SocialConversionStatus::None,
        ], $overrides));
    }
}
