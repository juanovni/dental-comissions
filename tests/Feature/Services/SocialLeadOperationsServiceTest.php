<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialLeadOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLeadOperationsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_actionable_query_includes_hot_and_pending_patient_leads(): void
    {
        $hotLead = $this->socialComment(['interest_score' => 80, 'hot_lead_at' => now()]);
        $pendingPatient = $this->socialComment(['conversion_status' => SocialConversionStatus::PendingPatientCreation]);
        $coldLead = $this->socialComment(['interest_score' => 10]);

        $ids = app(SocialLeadOperationsService::class)->queryActionableLeads()->pluck('id');

        $this->assertTrue($ids->contains($hotLead->id));
        $this->assertTrue($ids->contains($pendingPatient->id));
        $this->assertFalse($ids->contains($coldLead->id));
    }

    public function test_operations_update_lead_and_create_audit_actions(): void
    {
        $comment = $this->socialComment(['interest_score' => 80, 'hot_lead_at' => now()->subHours(5)]);
        $service = app(SocialLeadOperationsService::class);

        $this->assertTrue($service->isOverdue($comment));

        $service->scheduleFollowUp($comment, 12, 'Llamar manana.');
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::ScheduleFollowUp->value,
            'notes' => 'Llamar manana.',
        ]);

        $service->markContacted($comment->refresh(), 'Contactado por WhatsApp.');
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'follow_up_at' => null,
        ]);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::MarkAsContacted->value,
            'notes' => 'Contactado por WhatsApp.',
        ]);

        $lost = $this->socialComment(['interest_score' => 80, 'hot_lead_at' => now()]);
        $service->markLost($lost, 'precio');
        $this->assertDatabaseHas('social_comments', [
            'id' => $lost->id,
            'lost_reason' => 'precio',
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
