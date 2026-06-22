<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AppointmentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_from_social_lead_copies_attribution_and_updates_pipeline(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $procedure = Procedure::factory()->create(['name' => 'Implantes dentales']);
        [$comment, $identity, $post] = $this->socialLead([
            'patient_id' => $patient->id,
            'suggested_procedure_id' => $procedure->id,
        ]);

        $appointment = app(AppointmentCreationService::class)->createFromSocialLead($comment, [
            'scheduled_at' => now()->addDay()->setSecond(0),
            'duration_minutes' => 60,
            'status' => AppointmentStatus::Scheduled,
            'source' => AppointmentSource::WhatsappAi,
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
            'metadata' => ['intent' => 'ready_to_book'],
        ]);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame($comment->id, $appointment->social_comment_id);
        $this->assertSame($identity->id, $appointment->social_identity_id);
        $this->assertSame($post->id, $appointment->social_post_id);
        $this->assertSame($procedure->id, $appointment->procedure_id);
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertSame(AppointmentSource::WhatsappAi, $appointment->source);

        $comment->refresh();
        $this->assertSame(SocialConversionStatus::AppointmentCreated, $comment->conversion_status);
        $this->assertSame(SocialPipelineStage::Appointment, $comment->pipeline_stage);
        $this->assertSame($patient->id, $comment->converted_patient_id);
        $this->assertNotNull($comment->converted_at);

        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::AppointmentCreated->value,
            'performed_by' => $user->id,
        ]);
    }

    public function test_create_from_social_lead_allows_pending_appointment_without_patient(): void
    {
        [$comment] = $this->socialLead();

        $appointment = app(AppointmentCreationService::class)->createFromSocialLead($comment, [
            'source' => AppointmentSource::SmartLink,
            'notes' => 'Paciente pidio horarios, ficha pendiente.',
        ]);

        $this->assertNull($appointment->patient_id);
        $this->assertSame($comment->id, $appointment->social_comment_id);
        $this->assertSame(AppointmentStatus::PendingConfirmation, $appointment->status);
        $this->assertSame(AppointmentSource::SmartLink, $appointment->source);
        $this->assertSame(SocialConversionStatus::AppointmentCreated, $comment->refresh()->conversion_status);
    }

    private function socialLead(array $overrides = []): array
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
            'caption' => 'Video de implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'patient_id' => $overrides['patient_id'] ?? null,
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'suggested_procedure_id' => $overrides['suggested_procedure_id'] ?? null,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero agendar una valoracion',
            'tracking_token' => 'DNT-'.strtoupper(substr(uniqid(), -5)),
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'pipeline_stage' => SocialPipelineStage::Qualified,
        ]);

        return [$comment, $identity, $post];
    }
}
