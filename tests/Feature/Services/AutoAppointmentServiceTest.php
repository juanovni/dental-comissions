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
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\AppointmentCreationService;
use App\Services\AutoAppointmentService;
use App\Services\SocialCrmSettingsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoAppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutoAppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoAppointmentService::class);

        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_slot_duration'],
            ['value' => 45, 'value_type' => 'integer', 'label' => 'Duracion'],
        );

        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_clinic_open'],
            ['value' => '09:00', 'value_type' => 'string', 'label' => 'Apertura'],
        );

        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_auto_confirm'],
            ['value' => false, 'value_type' => 'boolean', 'label' => 'Auto confirm'],
        );
    }

    public function test_creates_appointment_when_intent_and_date_detected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07'));

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dr. Test']);
        $comment = $this->socialComment($procedure, $doctor);

        $agentResponse = [
            'intent' => 'appointment_interest',
            'closing_opportunity_score' => 85,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => '2026-07-08',
                'preferred_time_parsed' => '10:00',
                'preferred_date_text' => 'mañana',
                'preferred_time_text' => 'a las 10am',
                'intent_type' => 'appointment_interest',
                'intent_confidence' => 80,
                'extraction_source' => 'local_fallback',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNotNull($appointment);
        $this->assertSame('2026-07-08 10:00:00', $appointment->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(AppointmentStatus::PendingConfirmation, $appointment->status);
        $this->assertSame(AppointmentSource::WhatsappAi, $appointment->source);
        $this->assertSame($procedure->id, $appointment->procedure_id);
        $this->assertSame($doctor->id, $appointment->doctor_id);

        $comment->refresh();
        $this->assertSame(SocialConversionStatus::AppointmentCreated, $comment->conversion_status);
        $this->assertSame(SocialPipelineStage::Appointment, $comment->pipeline_stage);

        $action = $comment->actions()->where('action', SocialCommentActionType::AppointmentAutoCreated)->first();
        $this->assertNotNull($action);
        $this->assertSame($appointment->id, $action->external_response['appointment_id']);

        Carbon::setTestNow();
    }

    public function test_creates_appointment_with_default_time_when_only_date(): void
    {
        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dr. Test']);
        $comment = $this->socialComment($procedure, $doctor);

        $agentResponse = [
            'intent' => 'ready_to_book',
            'closing_opportunity_score' => 90,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => now()->next('Monday')->format('Y-m-d'),
                'preferred_time_parsed' => null,
                'preferred_date_text' => 'mañana',
                'preferred_time_text' => null,
                'intent_type' => 'ready_to_book',
                'intent_confidence' => 85,
                'extraction_source' => 'local_fallback',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNotNull($appointment);
        $this->assertStringContainsString('09:00', $appointment->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(AppointmentStatus::PendingConfirmation, $appointment->status);
    }

    public function test_returns_null_when_no_booking_intent(): void
    {
        $comment = $this->socialComment(Procedure::factory()->create(), Professional::factory()->doctor()->create());
        $agentResponse = [
            'intent' => 'information_seeking',
            'closing_opportunity_score' => 30,
            'appointment_candidate' => [
                'wants_appointment' => false,
                'preferred_date_parsed' => null,
                'preferred_time_parsed' => null,
                'intent_type' => null,
                'intent_confidence' => 0,
                'extraction_source' => 'none',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNull($appointment);
    }

    public function test_returns_null_when_no_date_parsed(): void
    {
        $comment = $this->socialComment(Procedure::factory()->create(), Professional::factory()->doctor()->create());
        $agentResponse = [
            'intent' => 'appointment_interest',
            'closing_opportunity_score' => 80,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => null,
                'preferred_time_parsed' => null,
                'intent_type' => 'appointment_interest',
                'intent_confidence' => 60,
                'extraction_source' => 'ai',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNull($appointment);
    }

    public function test_returns_null_when_date_is_in_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07'));

        $comment = $this->socialComment(Procedure::factory()->create(), Professional::factory()->doctor()->create());
        $agentResponse = [
            'intent' => 'ready_to_book',
            'closing_opportunity_score' => 95,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => '2026-07-06',
                'preferred_time_parsed' => '10:00',
                'intent_type' => 'ready_to_book',
                'intent_confidence' => 90,
                'extraction_source' => 'ai',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNull($appointment);

        Carbon::setTestNow();
    }

    public function test_auto_confirm_creates_confirmed_appointment(): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_auto_confirm'],
            ['value' => true, 'value_type' => 'boolean', 'label' => 'Auto confirm'],
        );

        $comment = $this->socialComment(Procedure::factory()->create(), Professional::factory()->doctor()->create());
        $agentResponse = [
            'intent' => 'appointment_interest',
            'closing_opportunity_score' => 85,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => now()->next('Monday')->format('Y-m-d'),
                'preferred_time_parsed' => '14:00',
                'intent_type' => 'appointment_interest',
                'intent_confidence' => 80,
                'extraction_source' => 'ai',
            ],
        ];

        $appointment = $this->service->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNotNull($appointment);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
    }

    public function test_logs_error_when_appointment_creation_throws(): void
    {
        $mockCreation = $this->createMock(AppointmentCreationService::class);
        $mockCreation->method('createFromSocialLead')
            ->willThrowException(new \RuntimeException('Doctor no disponible'));

        $this->app->instance(AppointmentCreationService::class, $mockCreation);
        $this->app->instance(AutoAppointmentService::class, null);

        $autoService = app(AutoAppointmentService::class);

        $comment = $this->socialComment(Procedure::factory()->create(), Professional::factory()->doctor()->create());
        $agentResponse = [
            'intent' => 'appointment_interest',
            'closing_opportunity_score' => 85,
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => now()->next('Monday')->format('Y-m-d'),
                'preferred_time_parsed' => '10:00',
                'intent_type' => 'appointment_interest',
                'intent_confidence' => 80,
                'extraction_source' => 'ai',
            ],
        ];

        $appointment = $autoService->createFromDetectedIntent($comment, $agentResponse);

        $this->assertNull($appointment);

        $action = $comment->actions()->where('action', SocialCommentActionType::Error)->first();
        $this->assertNotNull($action);
        $this->assertStringContainsString('Doctor no disponible', $action->notes);
    }

    private function socialComment(Procedure $procedure, ?Professional $doctor = null): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Test',
            'external_account_id' => 'test_' . uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_' . uniqid(),
            'caption' => 'Test',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_' . uniqid(),
            'username' => 'test_user',
            'display_name' => 'Test User',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'suggested_procedure_id' => $procedure->id,
            'suggested_doctor_id' => $doctor?->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_' . uniqid(),
            'author_name' => 'Test User',
            'author_username' => 'test_user',
            'comment_text' => 'Quiero agendar',
            'tracking_token' => 'DNT-TEST' . uniqid(),
        ]);
    }
}
