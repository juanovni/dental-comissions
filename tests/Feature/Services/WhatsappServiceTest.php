<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageStatus;
use App\Models\ActivityRecord;
use App\Models\Appointment;
use App\Models\AppointmentSlotOffer;
use App\Models\DoctorAssistantAssignment;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodCommissionRate;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappService $whatsappService;

    private array $geminiContent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->whatsappService = app(WhatsappService::class);
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-key',
        ]);
        $this->fakeGemini();
    }

    public function test_process_incoming_message_creates_whatsapp_message(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertInstanceOf(WhatsappMessage::class, $result);
        $this->assertEquals('+573001112233', $result->from_phone);
        $this->assertEquals('Test message', $result->message_body);
    }

    public function test_process_incoming_message_identifies_professional_by_phone(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertEquals($doctor->id, $result->professional_id);
    }

    public function test_process_incoming_message_handles_unknown_phone(): void
    {
        $payload = $this->buildPayload('+573009999999', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertNull($result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Processed, $result->status);
        $this->assertNotNull($result->social_comment_id);
        $this->assertDatabaseHas('social_comments', [
            'id' => $result->social_comment_id,
            'platform' => SocialPlatform::Whatsapp->value,
            'pipeline_stage' => 'new',
            'interest_score' => 20,
        ]);
    }

    public function test_whatsapp_first_availability_message_detects_procedure_and_offers_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));
        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_max_slots_offer', 3, 'integer');
        $this->setting('social_appointment_clinic_open', '08:00', 'string');
        $this->setting('social_appointment_clinic_close', '18:00', 'string');
        $this->setting('social_appointment_afternoon_start', '13:00', 'string');
        $this->setting('social_appointment_afternoon_end', '18:00', 'string');

        $procedure = Procedure::factory()->create([
            'name' => 'Ortodoncia invisible',
            'code' => 'ORTODONCIA_INVISIBLE',
            'category' => 'ortodoncia_invisible',
            'is_active' => true,
        ]);
        Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $result = $this->whatsappService->processIncomingMessage($this->buildPayload(
            '593985925100',
            'Hola buenas tardes tiene disponibilidad para odontología invisible es jueves en la tarde?',
        ));

        $comment = $result->socialComment;
        $offer = AppointmentSlotOffer::where('social_comment_id', $comment->id)->first();

        $this->assertNotNull($result);
        $this->assertSame($procedure->id, $comment->suggested_procedure_id);
        $this->assertSame('appointment_interest', $comment->ai_intent);
        $this->assertSame('2026-07-16 15:00:00', $comment->appointment_scheduled_at->format('Y-m-d H:i:s'));
        $this->assertNotNull($offer);
        $this->assertSame('afternoon', $offer->metadata['requested_period']);
        $this->assertSame('2026-07-16', $offer->metadata['requested_date']);
        $this->assertStringContainsString('Ortodoncia invisible', WhatsappMessage::where('direction', 'outgoing')->latest('id')->value('message_body'));
        $this->assertStringNotContainsString('No especificado', WhatsappMessage::where('direction', 'outgoing')->latest('id')->value('message_body'));

        Carbon::setTestNow();
    }

    public function test_whatsapp_first_confirmation_reuses_lead_after_appointment_created_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $procedure = Procedure::factory()->create(['name' => 'Valoracion dental', 'is_active' => true]);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dr. Demo']);

        $firstMessage = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Hola quiero una cita'),
        );
        $comment = $firstMessage->socialComment;
        $comment->update([
            'suggested_procedure_id' => $procedure->id,
            'suggested_doctor_id' => $doctor->id,
            'conversion_status' => SocialConversionStatus::AppointmentCreated,
        ]);
        $appointment = Appointment::factory()->create([
            'social_comment_id' => $comment->id,
            'social_identity_id' => $comment->social_identity_id,
            'procedure_id' => $procedure->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => '2026-07-16 15:45:00',
            'duration_minutes' => 45,
            'status' => AppointmentStatus::PendingConfirmation,
        ]);

        $confirmation = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Si'),
        );

        $this->assertSame($comment->id, $confirmation->social_comment_id);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->refresh()->status);
        $this->assertStringContainsString('confirmada', WhatsappMessage::where('direction', 'outgoing')->latest('id')->value('message_body'));

        Carbon::setTestNow();
    }

    public function test_duplicate_message_is_not_processed_twice(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');
        $result1 = $this->whatsappService->processIncomingMessage($payload);

        $result2 = $this->whatsappService->processIncomingMessage($payload);

        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals(1, WhatsappMessage::where('from_phone', '+573001112233')->count());
    }

    public function test_process_reply_ok_confirms_message(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $original = WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => WhatsappMessageStatus::Parsed,
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'original_test_123',
        ]);

        $replyPayload = $this->buildPayload('+573001112233', 'OK');
        $replyPayload['messages'][0]['context'] = ['id' => 'original_test_123'];

        $reply = $this->whatsappService->processIncomingMessage($replyPayload);

        $this->assertNotNull($reply);
        $this->assertEquals(WhatsappMessageStatus::Confirmed, $reply->status);

        $original->refresh();
        $this->assertEquals(WhatsappMessageStatus::Confirmed, $original->status);
    }

    public function test_process_reply_corregir_marks_needs_review(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $original = WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => WhatsappMessageStatus::Parsed,
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'original_test_456',
        ]);

        $replyPayload = $this->buildPayload('+573001112233', 'CORREGIR era resina');
        $replyPayload['messages'][0]['context'] = ['id' => 'original_test_456'];

        $reply = $this->whatsappService->processIncomingMessage($replyPayload);

        $this->assertNotNull($reply);
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $reply->status);
        $this->assertEquals('era resina', $reply->error_message);

        $original->refresh();
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $original->status);
    }

    public function test_assistant_with_one_assigned_doctor_registers_activity_for_that_doctor(): void
    {
        $this->seedPaymentMethods();

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Carlos Ramirez',
            'is_active' => true,
        ]);
        $assistant = Professional::factory()->create([
            'role' => 'assistant',
            'name' => 'Ana Garcia',
            'whatsapp_phone' => '+573001112233',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        DoctorAssistantAssignment::create([
            'doctor_id' => $doctor->id,
            'assistant_id' => $assistant->id,
            'is_active' => true,
        ]);

        $this->fakeGemini([
            'patient_name' => 'Maria Perez',
            'procedures' => [$procedure->name],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573001112233', 'Paciente: Maria Perez Procedimiento: Limpieza dental Pago: efectivo'),
        );

        $activity = ActivityRecord::first();

        $this->assertNotNull($result);
        $this->assertNotNull($activity);
        $this->assertEquals($assistant->id, $result->professional_id);
        $this->assertEquals($doctor->id, $activity->doctor_id);
        $this->assertTrue($activity->assistants()->whereKey($assistant->id)->exists());
    }

    public function test_assistant_with_multiple_doctors_uses_labeled_doctor(): void
    {
        $this->seedPaymentMethods();

        $firstDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Carlos Ramirez',
            'is_active' => true,
        ]);
        $secondDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dra. Laura Torres',
            'is_active' => true,
        ]);
        $assistant = Professional::factory()->create([
            'role' => 'assistant',
            'name' => 'Ana Garcia',
            'whatsapp_phone' => '+573001112233',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            DoctorAssistantAssignment::create([
                'doctor_id' => $doctor->id,
                'assistant_id' => $assistant->id,
                'is_active' => true,
            ]);
        }

        $this->fakeGemini([
            'patient_name' => 'Maria Perez',
            'procedures' => [$procedure->name],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573001112233', 'Doctor: Laura Torres, Paciente: Maria Perez, Procedimiento: Limpieza dental, Pago: efectivo'),
        );

        $activity = ActivityRecord::first();

        $this->assertNotNull($activity);
        $this->assertEquals($secondDoctor->id, $activity->doctor_id);
        $this->assertTrue($activity->assistants()->whereKey($assistant->id)->exists());
    }

    public function test_assistant_with_multiple_doctors_uses_doctor_mentioned_at_message_start(): void
    {
        $this->seedPaymentMethods();

        $firstDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Carlos Ramirez',
            'is_active' => true,
        ]);
        $secondDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Juan Constantine Murillo',
            'is_active' => true,
        ]);
        $assistant = Professional::factory()->create([
            'role' => 'assistant',
            'name' => 'Ana Garcia',
            'whatsapp_phone' => '+573007778899',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            DoctorAssistantAssignment::create([
                'doctor_id' => $doctor->id,
                'assistant_id' => $assistant->id,
                'is_active' => true,
            ]);
        }

        $this->fakeGemini([
            'patient_name' => 'Roberto Gomez',
            'procedures' => [$procedure->name],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573007778899', 'Dr. Juan Constantine, limpieza dental para Roberto Gomez, pago efectivo'),
        );

        $activity = ActivityRecord::first();

        $this->assertNotNull($activity);
        $this->assertEquals($secondDoctor->id, $activity->doctor_id);
        $this->assertTrue($activity->assistants()->whereKey($assistant->id)->exists());
    }

    public function test_assistant_partial_doctor_name_is_rejected_when_ambiguous(): void
    {
        $firstDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Juan Constantine Murillo',
            'is_active' => true,
        ]);
        $secondDoctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Juan Constantine Perez',
            'is_active' => true,
        ]);
        $assistant = Professional::factory()->create([
            'role' => 'assistant',
            'name' => 'Ana Garcia',
            'whatsapp_phone' => '+573007778899',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            DoctorAssistantAssignment::create([
                'doctor_id' => $doctor->id,
                'assistant_id' => $assistant->id,
                'is_active' => true,
            ]);
        }

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573007778899', 'Dr. Juan Constantine, limpieza dental para Roberto Gomez, pago efectivo'),
        );

        $this->assertNotNull($result);
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $result->status);
        $this->assertEquals(0, ActivityRecord::count());
    }

    public function test_assistant_with_multiple_doctors_without_doctor_label_needs_review(): void
    {
        $firstDoctor = Professional::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $secondDoctor = Professional::factory()->create(['role' => 'doctor', 'is_active' => true]);
        $assistant = Professional::factory()->create([
            'role' => 'assistant',
            'whatsapp_phone' => '+573001112233',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            DoctorAssistantAssignment::create([
                'doctor_id' => $doctor->id,
                'assistant_id' => $assistant->id,
                'is_active' => true,
            ]);
        }

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573001112233', 'Paciente: Maria Perez Procedimiento: Limpieza dental Pago: efectivo'),
        );

        $this->assertNotNull($result);
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $result->status);
        $this->assertStringContainsString('perteneces a varios doctores', $result->error_message);
        $this->assertEquals(0, ActivityRecord::count());
    }

    public function test_social_tracking_token_takes_precedence_over_professional_activity_flow(): void
    {
        $professional = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $account = SocialAccount::create([
            'platform' => 'instagram',
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => 'instagram',
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Limpieza dental',
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => 'instagram',
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'comment_text' => 'Me interesa esta limpieza dental',
            'tracking_token' => 'DNT-ABC12',
        ]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('+573001112233', 'Hola, vengo de redes sociales. Mi codigo es DNT-ABC12.'),
        );

        $this->assertNotNull($result);
        $this->assertEquals($professional->id, $result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Processed, $result->status);
        $this->assertEquals(0, ActivityRecord::count());
        $this->assertNotNull($comment->refresh()->social_identity_id);
    }

    public function test_unknown_phone_creates_whatsapp_lead_instead_of_activity_without_tracking_token(): void
    {
        Procedure::factory()->create(['name' => 'Extraccion quirurgica', 'is_active' => true]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Procedimiento: extraccion quirurgica Paciente: Antonio Pepe Auxiliar: Ana Garcia Pago: Efectivo'),
        );

        $this->assertNotNull($result);
        $this->assertNull($result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Processed, $result->status);
        $this->assertNotNull($result->social_comment_id);
        $this->assertEquals(0, ActivityRecord::count());
        $this->assertDatabaseHas('social_comments', [
            'id' => $result->social_comment_id,
            'platform' => SocialPlatform::Whatsapp->value,
            'pipeline_stage' => 'new',
        ]);
    }

    public function test_unknown_phone_with_tracking_token_is_processed_as_social_lead(): void
    {
        $account = SocialAccount::create([
            'platform' => 'instagram',
            'account_name' => 'Clinica Dental IG',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => 'instagram',
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_post_id' => $post->id,
            'platform' => 'instagram',
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'comment_text' => 'Me interesa una valoracion',
            'tracking_token' => 'DNT-YG4SV',
        ]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Hola, Mi codigo es DNT-YG4SV.'),
        );

        $this->assertNotNull($result);
        $this->assertNull($result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Processed, $result->status);
        $this->assertEquals(0, ActivityRecord::count());
        $this->assertNotNull($comment->refresh()->social_identity_id);
    }

    public function test_unknown_tracking_token_does_not_fall_back_to_activity_flow(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+593985925100',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Hola, vengo de redes sociales. Mi codigo es DNT-FMNPQ.'),
        );

        $this->assertNotNull($result);
        $this->assertEquals(WhatsappMessageStatus::Failed, $result->status);
        $this->assertStringContainsString('Codigo de lead no encontrado: DNT-FMNPQ', $result->error_message);
        $this->assertEquals(0, ActivityRecord::count());
    }

    public function test_malformed_tracking_token_from_professional_does_not_fall_back_to_activity_flow(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+593985925100',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $result = $this->whatsappService->processIncomingMessage(
            $this->buildPayload('593985925100', 'Hola, vengo de redes sociales. Mi codigo es DNT-BY'),
        );

        $this->assertNotNull($result);
        $this->assertEquals($doctor->id, $result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Failed, $result->status);
        $this->assertStringContainsString('Codigo de lead incompleto o invalido', $result->error_message);
        $this->assertEquals(0, ActivityRecord::count());
    }

    private function fakeGemini(?array $content = null): void
    {
        $this->geminiContent = $content ?? [
            'patient_name' => '',
            'procedures' => [],
            'assistants' => [],
            'payment_method' => '',
            'date' => now()->format('Y-m-d'),
            'needs_review' => true,
            'review_notes' => 'No se pudo procesar el mensaje',
        ];

        Http::fake(fn () => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode($this->geminiContent)],
                        ],
                    ],
                ],
            ],
        ]));
    }

    private function buildPayload(string $phone, string $message): array
    {
        return [
            'messages' => [
                [
                    'from' => $phone,
                    'id' => 'test_'.uniqid(),
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ],
            ],
        ];
    }

    private function setting(string $key, mixed $value, string $type): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'value_type' => $type, 'label' => $key, 'is_active' => true],
        );
    }

    private function seedPaymentMethods(): void
    {
        $paymentMethod = PaymentMethod::create([
            'name' => 'Efectivo',
            'code' => 'EFECTIVO',
            'aliases' => ['efectivo', 'efe'],
            'is_active' => true,
        ]);

        PaymentMethodCommissionRate::create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1.25,
            'is_active' => true,
        ]);
    }
}
