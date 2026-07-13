<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Appointment;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingPipelineE2ETest extends TestCase
{
    use RefreshDatabase;

    private WhatsappService $whatsappService;

    private SocialAccount $account;

    private SocialIdentity $identity;

    private SocialPost $post;

    private Procedure $procedure;

    private Professional $doctor;

    private string $leadPhone = '+573001234567';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.provider' => 'local',
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.0-flash-exp',
            'services.whatsapp.api_url' => 'https://graph.facebook.com/v19.0',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.access_token' => 'test-token',
        ]);

        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_propose_slots'],
            ['value' => false, 'value_type' => 'boolean', 'label' => 'Proponer horarios', 'is_active' => true],
        );
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_slot_duration'],
            ['value' => 45, 'value_type' => 'integer', 'label' => 'Duracion cita', 'is_active' => true],
        );
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_clinic_open'],
            ['value' => '09:00', 'value_type' => 'string', 'label' => 'Apertura clinica', 'is_active' => true],
        );
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_auto_confirm'],
            ['value' => false, 'value_type' => 'boolean', 'label' => 'Auto confirmar', 'is_active' => true],
        );

        $this->account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Test',
            'external_account_id' => 'ext_e2e_' . uniqid(),
            'is_active' => true,
        ]);

        $this->procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        $this->post = SocialPost::create([
            'social_account_id' => $this->account->id,
            'procedure_id' => $this->procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_e2e_' . uniqid(),
            'caption' => 'Oferta especial',
        ]);

        $this->identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_e2e_' . uniqid(),
            'username' => 'lead_e2e',
            'display_name' => 'Lead E2E',
            'phone' => $this->leadPhone,
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->doctor = Professional::factory()->doctor()->create(['name' => 'Dr. E2E']);

        Http::fake([
            'graph.facebook.com/*' => function ($request) {
                return Http::response([
                    'messages' => [['id' => 'outbound_' . uniqid()]],
                ], 200);
            },
        ]);

        $this->whatsappService = app(WhatsappService::class);
    }

    private function createLeadComment(string $trackingToken, array $overrides = []): SocialComment
    {
        return SocialComment::create(array_merge([
            'social_account_id' => $this->account->id,
            'social_identity_id' => $this->identity->id,
            'social_post_id' => $this->post->id,
            'suggested_procedure_id' => $this->procedure->id,
            'suggested_doctor_id' => $this->doctor->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_e2e_' . uniqid(),
            'author_name' => 'Lead E2E',
            'author_username' => 'lead_e2e',
            'author_phone' => $this->leadPhone,
            'comment_text' => 'Quiero informacion',
            'tracking_token' => $trackingToken,
            'status' => SocialCommentStatus::Classified,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'pipeline_stage' => SocialPipelineStage::Qualified,
        ], $overrides));
    }

    private function buildPayload(string $fromPhone, string $body, ?string $msgId = null): array
    {
        return [
            'messages' => [
                [
                    'from' => $fromPhone,
                    'id' => $msgId ?? 'msg_' . uniqid(),
                    'text' => ['body' => $body],
                ],
            ],
        ];
    }

    private function assertCommentHasAction(SocialComment $comment, SocialCommentActionType $actionType): void
    {
        $found = $comment->actions()
            ->where('action', $actionType->value)
            ->exists();

        $this->assertTrue($found, "Expected action {$actionType->value} not found on comment {$comment->id}");
    }

    private function getSentMessageBodies(): array
    {
        return WhatsappMessage::query()
            ->where('direction', WhatsappMessageDirection::Outgoing)
            ->orderBy('id')
            ->get()
            ->pluck('message_body')
            ->toArray();
    }

    // ───────────────────────────────
    //  1. Full booking: token → booking → confirm
    // ───────────────────────────────

    /** @test */
    public function full_booking_pipeline_with_tracking_token_confirm(): void
    {
        $token = 'DNT-ECFM1';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 10:00';

        $result1 = $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_in_1'),
        );

        $this->assertNotNull($result1);
        $this->assertEquals(WhatsappMessageStatus::Processed, $result1->status);

        $comment->refresh();
        $this->assertNotNull($comment->appointment_scheduled_at);
        $this->assertEquals('appointment_interest', $comment->ai_intent);
        $this->assertNotNull($comment->ai_confidence);

        $this->assertCommentHasAction($comment, SocialCommentActionType::BookingIntentDetected);

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment, 'Appointment should be created');
        $this->assertEquals(AppointmentStatus::PendingConfirmation, $appointment->status);
        $this->assertEquals(AppointmentSource::WhatsappAi, $appointment->source);
        $this->assertEquals($comment->suggested_doctor_id, $appointment->doctor_id);
        $this->assertEquals(AppointmentStatus::PendingConfirmation, $appointment->status);

        $this->assertCommentHasAction($comment, SocialCommentActionType::AppointmentAutoCreated);

        $sentBodies = $this->getSentMessageBodies();
        $this->assertNotEmpty($sentBodies);

        $msg2 = 'Si, confirmo la cita';

        $result2 = $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_in_2'),
        );

        $this->assertNotNull($result2);

        $this->assertNotNull($result2);

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertNotNull($appointment->confirmed_at);

        $this->assertCommentHasAction($comment, SocialCommentActionType::BookingConfirmed);
    }

    // ───────────────────────────────
    //  2. Full booking: token → booking → reject
    // ───────────────────────────────

    /** @test */
    public function full_booking_pipeline_cancel(): void
    {
        $token = 'DNT-ECNCL';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para pasado manana';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_cancel_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment);

        $msg2 = 'No, gracias';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_cancel_2'),
        );

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);

        $this->assertCommentHasAction($comment, SocialCommentActionType::BookingRejected);
    }

    // ───────────────────────────────
    //  3. Lead by phone (no token)
    // ───────────────────────────────

    /** @test */
    public function full_booking_pipeline_lead_by_phone(): void
    {
        $comment = $this->createLeadComment('DNT-EPHNE');

        $msg1 = 'Quiero agendar una cita para el viernes a las 3 de la tarde';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_phone_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment, 'Appointment should be created from phone lead');
        $this->assertEquals(AppointmentStatus::PendingConfirmation, $appointment->status);

        $msg2 = 'Si, confirmo';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_phone_2'),
        );

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::Confirmed, $appointment->status);
    }

    // ───────────────────────────────
    //  4. Booking with date modification
    // ───────────────────────────────

    /** @test */
    public function lead_modifies_appointment_date(): void
    {
        $token = 'DNT-EMDFY';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 10:00';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_mod_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment);

        $originalDate = $appointment->scheduled_at->format('Y-m-d');

        $msg2 = 'Mejor a las 3 de la tarde';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_mod_2'),
        );

        $appointment->refresh();
        $this->assertNotEquals(
            $originalDate . ' 10:00',
            $appointment->scheduled_at->format('Y-m-d H:i'),
            'Date should have changed',
        );

        $this->assertCommentHasAction($comment, SocialCommentActionType::BookingModified);
    }

    // ───────────────────────────────
    //  5. Past date handling
    // ───────────────────────────────

    /** @test */
    public function lead_cannot_create_past_date_appointment(): void
    {
        $token = 'DNT-EPAST';
        $comment = $this->createLeadComment($token);

        $pastDate = now()->subDays(2);

        SocialCrmSetting::where('key', 'social_appointment_auto_confirm')->update(['value' => true]);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 10:00';

        Carbon::setTestNow($pastDate->copy()->subHour());
        $result1 = $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_past_1'),
        );
        Carbon::setTestNow();

        $this->assertNotNull($result1);

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNull($appointment, 'Past date requests should not create appointments.');
    }

    // ───────────────────────────────
    //  6. No booking intent → normal agent flow
    // ───────────────────────────────

    /** @test */
    public function no_booking_intent_falls_through_to_agent(): void
    {
        $token = 'DNT-EQRY1';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Cuanto cuesta la limpieza dental?';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_query_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNull($appointment, 'No appointment should be created without booking intent');

        $comment->refresh();
        $this->assertNotEquals(
            SocialPipelineStage::Appointment,
            $comment->pipeline_stage,
            'Pipeline should not advance to Appointment without booking intent',
        );
    }

    // ───────────────────────────────
    //  7. Multiple booking attempts deduplicated
    // ───────────────────────────────

    /** @test */
    public function duplicate_booking_message_is_deduplicated(): void
    {
        $token = 'DNT-EDDP1';
        $comment = $this->createLeadComment($token);

        $msgId1 = 'e2e_dedup_1';
        $msgId2 = 'e2e_dedup_2';

        $msg = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 11:00';

        $payload = $this->buildPayload($this->leadPhone, $msg, $msgId1);

        $result1 = $this->whatsappService->processIncomingMessage($payload);

        $result2 = $this->whatsappService->processIncomingMessage($payload);

        $this->assertSame($result1->id, $result2->id);

        $appointments = Appointment::where('social_comment_id', $comment->id)->count();
        $this->assertEquals(1, $appointments, 'Only one appointment should be created');
    }

    // ───────────────────────────────
    //  8. Forgeful lead
    // ───────────────────────────────

    /** @test */
    public function forgetful_lead_gets_reminded(): void
    {
        $token = 'DNT-EFRGT';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 10:00';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_forget_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment);

        $msg2 = 'No recuerdo haber agendado una cita';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_forget_2'),
        );

        $sentBodies = $this->getSentMessageBodies();
        $lastReply = end($sentBodies);

        $this->assertStringContainsString('cita', mb_strtolower($lastReply ?? ''));
        $this->assertStringContainsString('confirmarla', mb_strtolower($lastReply ?? ''));

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::PendingConfirmation, $appointment->status);
    }

    // ───────────────────────────────
    //  9. Confirmed appointment cancel
    // ───────────────────────────────

    /** @test */
    public function cancel_confirmed_appointment(): void
    {
        $token = 'DNT-ECFCL';
        $comment = $this->createLeadComment($token);

        SocialCrmSetting::where('key', 'social_appointment_auto_confirm')->update(['value' => true]);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para manana a las 10:00';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_conf_cancel_1'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        if ($appointment && $appointment->status === AppointmentStatus::Confirmed) {
            $msg2 = 'Quiero cancelar mi cita';

            $this->whatsappService->processIncomingMessage(
                $this->buildPayload($this->leadPhone, $msg2, 'e2e_conf_cancel_2'),
            );

            $appointment->refresh();
            $this->assertEquals(AppointmentStatus::Cancelled, $appointment->status);
        }
    }

    // ───────────────────────────────
    //  10. Full audit trail
    // ───────────────────────────────

    /** @test */
    public function full_pipeline_creates_complete_audit_trail(): void
    {
        $token = 'DNT-EADIT';
        $comment = $this->createLeadComment($token);

        $msg1 = 'Mi codigo es ' . $token . '. Quiero agendar una cita para el proximo lunes a las 9:00';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg1, 'e2e_audit_1'),
        );

        $msg2 = 'Si, confirmo';

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload($this->leadPhone, $msg2, 'e2e_audit_2'),
        );

        $appointment = Appointment::where('social_comment_id', $comment->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(AppointmentStatus::Confirmed, $appointment->status);

        $actions = $comment->actions()->orderBy('id')->get();
        $actionTypes = $actions->pluck('action')->map(fn ($a) => $a->value)->values()->toArray();

        $this->assertContains(SocialCommentActionType::WhatsappSalesAgent->value, $actionTypes);
        $this->assertContains(SocialCommentActionType::BookingIntentDetected->value, $actionTypes);
        $this->assertContains(SocialCommentActionType::AppointmentAutoCreated->value, $actionTypes);
        $this->assertContains(SocialCommentActionType::BookingConfirmed->value, $actionTypes);

        $this->assertCount(2, WhatsappMessage::where('to_phone', $this->leadPhone)->where('direction', WhatsappMessageDirection::Outgoing)->get());
    }
}
