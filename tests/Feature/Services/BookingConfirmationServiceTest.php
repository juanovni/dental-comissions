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
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\BookingConfirmationService;
use App\Services\GoogleCalendarService;
use App\Services\AppointmentWorkflowService;
use App\Services\WhatsappSalesAgentService;
use App\Services\AppointmentIntentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $intentService = $this->createMock(AppointmentIntentService::class);
        $agentService = $this->createMock(WhatsappSalesAgentService::class);
        $calendarService = $this->createMock(GoogleCalendarService::class);
        $workflow = new AppointmentWorkflowService($calendarService);

        $this->service = new BookingConfirmationService(
            $workflow,
            $intentService,
            $agentService,
        );

        $intentService->method('extractFromText')
            ->willReturn([
                'has_intent' => true,
                'intent_type' => null,
                'preferred_date_text' => null,
                'preferred_time_text' => null,
                'preferred_date_parsed' => null,
                'preferred_time_parsed' => null,
                'confidence' => 0,
                'extraction_source' => 'none',
            ]);

        $agentService->method('respond')
            ->willReturn([
                'source' => 'fallback',
                'reply' => 'Respuesta generica.',
                'intent' => 'information_seeking',
                'closing_opportunity_score' => 30,
                'requires_human_handoff' => false,
                'handoff_reason' => '',
                'suggested_pipeline_stage' => 'lead',
                'clinical_safety_flag' => false,
                'appointment_candidate' => [
                    'wants_appointment' => false,
                    'preferred_date_text' => null,
                    'preferred_time_text' => null,
                ],
            ]);
    }

    /** @test */
    public function detect_locally_returns_confirmed_for_ok(): void
    {
        $result = $this->service->detectLocally('OK');
        $this->assertSame('confirmed', $result);
    }

    /** @test */
    public function detect_locally_returns_confirmed_for_si(): void
    {
        $result = $this->service->detectLocally('Si');
        $this->assertSame('confirmed', $result);
    }

    /** @test */
    public function detect_locally_returns_confirmed_for_dale(): void
    {
        $result = $this->service->detectLocally('Dale, confirmo');
        $this->assertSame('confirmed', $result);
    }

    /** @test */
    public function detect_locally_returns_confirmed_for_de_acuerdo(): void
    {
        $result = $this->service->detectLocally('De acuerdo, agendalo');
        $this->assertSame('confirmed', $result);
    }

    /** @test */
    public function detect_locally_returns_confirmed_for_perfecto(): void
    {
        $result = $this->service->detectLocally('Perfecto, gracias');
        $this->assertSame('confirmed', $result);
    }

    /** @test */
    public function detect_locally_returns_rejected_for_no(): void
    {
        $result = $this->service->detectLocally('No');
        $this->assertSame('rejected', $result);
    }

    /** @test */
    public function detect_locally_returns_rejected_for_no_gracias(): void
    {
        $result = $this->service->detectLocally('No gracias');
        $this->assertSame('rejected', $result);
    }

    /** @test */
    public function detect_locally_returns_rejected_for_cancelar(): void
    {
        $result = $this->service->detectLocally('Cancelalo por favor');
        $this->assertSame('rejected', $result);
    }

    /** @test */
    public function detect_locally_returns_modified_for_time_change(): void
    {
        $result = $this->service->detectLocally('Mejor a las 3 de la tarde');
        $this->assertSame('modified', $result);
    }

    /** @test */
    public function detect_locally_returns_modified_for_otro_dia(): void
    {
        $result = $this->service->detectLocally('Otro dia');
        $this->assertSame('modified', $result);
    }

    /** @test */
    public function detect_locally_returns_modified_for_reprogramar(): void
    {
        $result = $this->service->detectLocally('Podemos reprogramar?');
        $this->assertSame('modified', $result);
    }

    /** @test */
    public function detect_locally_returns_not_booking_response_for_generic_query(): void
    {
        $result = $this->service->detectLocally('Cuanto cuesta el tratamiento?');
        $this->assertSame('not_booking_response', $result);
    }

    /** @test */
    public function detect_locally_returns_not_booking_response_for_greeting(): void
    {
        $result = $this->service->detectLocally('Hola, buenos dias');
        $this->assertSame('not_booking_response', $result);
    }

    /** @test */
    public function handle_message_confirms_appointment_on_si(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment);

        $message = $this->mockMessage($comment, 'Si, confirmo la cita');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('confirmed', $result['action']);
        $this->assertStringContainsString('confirmada', mb_strtolower($result['reply']));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertNotNull($appointment->confirmed_at);

        $action = $comment->actions()->latest()->first();
        $this->assertNotNull($action);
        $this->assertSame(SocialCommentActionType::BookingConfirmed, $action->action);
    }

    /** @test */
    public function handle_message_cancels_appointment_on_no(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment);

        $message = $this->mockMessage($comment, 'No, no quiero la cita');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('rejected', $result['action']);
        $this->assertStringContainsString('cancelado', mb_strtolower($result['reply']));
        $this->assertStringContainsString('futuro', mb_strtolower($result['reply']));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);

        $action = $comment->actions()->latest()->first();
        $this->assertNotNull($action);
        $this->assertSame(SocialCommentActionType::BookingRejected, $action->action);
    }

    /** @test */
    public function handle_message_returns_no_decision_for_unrelated_message(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment);

        $message = $this->mockMessage($comment, 'Que otros tratamientos tienen?');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('no_decision', $result['action']);
    }

    /** @test */
    public function build_reply_confirmed_formats_correctly(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'scheduled_at' => Carbon::parse('2026-07-15 10:00'),
        ]);

        $reply = $this->service->buildReply('confirmed', $appointment);

        $this->assertStringContainsString('confirmada', mb_strtolower($reply));
        $this->assertStringContainsString($appointment->doctor->name, $reply);
    }

    /** @test */
    public function build_reply_rejected_formats_correctly(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Cancelled,
        ]);

        $reply = $this->service->buildReply('rejected', $appointment);

        $this->assertStringContainsString('cancelado', mb_strtolower($reply));
        $this->assertStringContainsString('futuro', mb_strtolower($reply));
    }

    private function createSocialComment(): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'test_account',
            'external_account_id' => 'ext_' . uniqid(),
            'access_token' => 'test-token',
            'is_active' => true,
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
        $procedure = Procedure::factory()->create();
        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_' . uniqid(),
            'caption' => 'Test',
        ]);
        $doctor = Professional::factory()->doctor()->create();

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'suggested_procedure_id' => $procedure->id,
            'suggested_doctor_id' => $doctor->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_' . uniqid(),
            'author_name' => 'Test User',
            'author_username' => 'test_user',
            'comment_text' => 'Quiero agendar',
            'tracking_token' => 'DNT-TEST' . uniqid(),
            'conversion_status' => SocialConversionStatus::WhatsappStarted,
            'pipeline_stage' => SocialPipelineStage::Appointment,
        ]);
    }

    private function createPendingAppointment(SocialComment $comment, array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'social_comment_id' => $comment->id,
            'patient_id' => null,
            'status' => AppointmentStatus::PendingConfirmation,
            'source' => AppointmentSource::WhatsappAi,
        ], $overrides));
    }

    private function mockMessage(SocialComment $comment, string $body): \App\Models\WhatsappMessage
    {
        $message = new \App\Models\WhatsappMessage();
        $message->social_comment_id = $comment->id;
        $message->message_body = $body;
        $message->direction = \App\Enums\WhatsappMessageDirection::Incoming;
        $message->from_phone = '+573001234567';
        $message->to_phone = 'whatsapp:123456789';
        $message->status = \App\Enums\WhatsappMessageStatus::Received;

        return $message;
    }

    // ─── Edge Cases ───

    /** @test */
    public function detect_locally_returns_forgetful_for_no_recuerdo(): void
    {
        $result = $this->service->detectLocally('No recuerdo haber agendado una cita');
        $this->assertSame('forgetful', $result);
    }

    /** @test */
    public function detect_locally_returns_forgetful_for_de_que_cita(): void
    {
        $result = $this->service->detectLocally('De que cita me hablas?');
        $this->assertSame('forgetful', $result);
    }

    /** @test */
    public function detect_locally_forgetful_takes_priority_over_confirmed(): void
    {
        $result = $this->service->detectLocally('No recuerdo, de que se trata?');
        $this->assertSame('forgetful', $result);
    }

    /** @test */
    public function handle_message_forgetful_returns_reminder_reply(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'scheduled_at' => now()->addDays(3),
        ]);

        $message = $this->mockMessage($comment, 'No recuerdo ninguna cita');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('forgetful', $result['action']);
        $this->assertStringContainsString('cita', mb_strtolower($result['reply']));
        $this->assertStringContainsString('confirmarla', mb_strtolower($result['reply']));
    }

    /** @test */
    public function handle_message_confirmed_appointment_cancel_request(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'status' => AppointmentStatus::Confirmed,
            'scheduled_at' => now()->addDays(3),
        ]);

        $message = $this->mockMessage($comment, 'Quiero cancelar mi cita por favor');

        $result = $this->service->handleMessage($comment, $message, $appointment);
        $this->assertSame('rejected', $result['action']);
        $this->assertStringContainsString('cancelado', mb_strtolower($result['reply']));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
    }

    /** @test */
    public function handle_message_confirmed_appointment_reschedule_request(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'status' => AppointmentStatus::Confirmed,
            'scheduled_at' => now()->addDays(3),
        ]);

        $message = $this->mockMessage($comment, 'Quiero cambiar la fecha de mi cita');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('modified', $result['action']);
        $this->assertStringContainsString('cambio de horario', mb_strtolower($result['reply']));
    }

    /** @test */
    public function handle_message_past_date_returns_reprompt(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'status' => AppointmentStatus::PendingConfirmation,
            'scheduled_at' => now()->subDays(2),
        ]);

        $message = $this->mockMessage($comment, 'Si, confirmo');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('past_date', $result['action']);
        $this->assertStringContainsString('ya paso', mb_strtolower($result['reply']));
        $this->assertStringContainsString('nueva cita', mb_strtolower($result['reply']));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::PendingConfirmation, $appointment->status);
    }

    /** @test */
    public function is_appointment_past_returns_true_for_past_date(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->subDay(),
        ]);

        $this->assertTrue($this->service->isAppointmentPast($appointment));
    }

    /** @test */
    public function is_appointment_past_returns_false_for_future_date(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->addDays(5),
        ]);

        $this->assertFalse($this->service->isAppointmentPast($appointment));
    }

    /** @test */
    public function is_appointment_past_returns_false_for_null_date(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => null,
        ]);

        $this->assertFalse($this->service->isAppointmentPast($appointment));
    }

    /** @test */
    public function is_appointment_stale_returns_true_for_old_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $this->assertTrue($this->service->isAppointmentStale($appointment));
    }

    /** @test */
    public function is_appointment_stale_returns_false_for_recent_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->service->isAppointmentStale($appointment));
    }

    /** @test */
    public function build_reply_forgetful_formats_correctly(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->addDays(3),
        ]);

        $reply = $this->service->buildReply('forgetful', $appointment);

        $this->assertStringContainsString('cita', mb_strtolower($reply));
        $this->assertStringContainsString('confirmarla', mb_strtolower($reply));
    }

    /** @test */
    public function build_reply_past_date_formats_correctly(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->subDays(2),
        ]);

        $reply = $this->service->buildReply('past_date', $appointment);

        $this->assertStringContainsString('ya paso', mb_strtolower($reply));
        $this->assertStringContainsString('nueva cita', mb_strtolower($reply));
    }

    /** @test */
    public function reject_booking_resets_conversion_status(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'status' => AppointmentStatus::Confirmed,
        ]);

        $message = $this->mockMessage($comment, 'No, cancelalo');

        $this->service->handleMessage($comment, $message, $appointment);

        $comment->refresh();
        $this->assertNull($comment->appointment_scheduled_at);
    }

    /** @test */
    public function confirmed_appointment_cancel_detected_with_phrase(): void
    {
        $result = $this->service->detectLocally('No voy a poder ir', true);
        $this->assertSame('rejected', $result);
    }

    /** @test */
    public function confirmed_appointment_reschedule_detected_with_phrase(): void
    {
        $result = $this->service->detectLocally('Quiero cambiar la fecha', true);
        $this->assertSame('modified', $result);
    }

    /** @test */
    public function pending_ok_with_past_date_returns_past_date_not_confirmed(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'scheduled_at' => now()->subDays(1),
        ]);

        $message = $this->mockMessage($comment, 'OK');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('past_date', $result['action']);
        $appointment->refresh();
        $this->assertSame(AppointmentStatus::PendingConfirmation, $appointment->status);
    }

    /** @test */
    public function forgetful_on_confirmed_appointment_shows_details(): void
    {
        $comment = $this->createSocialComment();
        $appointment = $this->createPendingAppointment($comment, [
            'status' => AppointmentStatus::Confirmed,
            'scheduled_at' => now()->addDays(3),
        ]);

        $message = $this->mockMessage($comment, 'No recuerdo haber agendado');

        $result = $this->service->handleMessage($comment, $message, $appointment);

        $this->assertSame('forgetful', $result['action']);

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
    }
}
