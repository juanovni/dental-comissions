<?php

namespace Tests\Feature\Services;

use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\AppointmentSlotOffer;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\AppointmentSlotOfferService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSlotOfferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_offer_and_confirms_selected_option(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 30, 'integer');
        $this->setting('social_appointment_slot_hold_minutes', 10, 'integer');

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $message = $this->message($comment, 'Quiero una cita el 18 de julio en la tarde');

        $offer = app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment, $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => '2026-07-18',
                'preferred_time_parsed' => null,
                'preferred_period' => 'afternoon',
                'intent_type' => 'appointment_interest',
            ],
        ]);

        $this->assertInstanceOf(AppointmentSlotOffer::class, $offer);
        $this->assertSame('pending', $offer->status);
        $this->assertCount(3, $offer->metadata['options']);
        $this->assertStringContainsString('18 de julio', app(AppointmentSlotOfferService::class)->buildOfferReply($offer));

        $appointment = app(AppointmentSlotOfferService::class)->confirmFromToken($offer, 2);

        $this->assertNotNull($appointment->scheduled_at);
        $this->assertSame($doctor->id, $appointment->doctor_id);
        $this->assertSame('selected', $offer->refresh()->status);
        $this->assertSame(2, $offer->selected_option_index);

        Carbon::setTestNow();
    }

    public function test_generic_booking_uses_default_procedure_for_offer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');

        $defaultProcedure = Procedure::factory()->create(['name' => 'Valoracion dental']);
        $this->setting('social_appointment_default_procedure_id', $defaultProcedure->id, 'integer');

        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($defaultProcedure, $doctor);
        $comment->forceFill(['suggested_procedure_id' => null])->save();
        $comment->unsetRelation('suggestedProcedure');
        $message = $this->message($comment, 'Quiero una cita el lunes en la tarde');

        $offer = app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment, $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => '2026-07-20',
                'preferred_time_parsed' => null,
                'preferred_period' => 'afternoon',
                'intent_type' => 'appointment_interest',
            ],
        ]);

        $this->assertInstanceOf(AppointmentSlotOffer::class, $offer);
        $this->assertTrue($offer->metadata['is_default_procedure']);
        $this->assertSame($defaultProcedure->id, $offer->metadata['procedure_id']);
        $this->assertSame($defaultProcedure->id, $offer->metadata['options'][0]['procedure_id']);
        $this->assertStringContainsString('no tenemos un procedimiento específico', app(AppointmentSlotOfferService::class)->buildOfferReply($offer));
        $this->assertSame($defaultProcedure->id, $comment->refresh()->suggested_procedure_id);

        Carbon::setTestNow();
    }

    public function test_new_time_question_does_not_select_third_option(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $message = $this->message($comment, 'Quiero una cita');

        app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment, $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => null,
                'preferred_time_parsed' => null,
                'preferred_period' => null,
                'intent_type' => 'appointment_interest',
            ],
        ]);

        $newQuestion = $this->message($comment, 'Hay citas para el dia miercoles 15 a las 3pm?');

        $this->assertNull(app(AppointmentSlotOfferService::class)->handleSelection($comment, $newQuestion));
        $this->assertDatabaseMissing('appointments', ['social_comment_id' => $comment->id]);

        Carbon::setTestNow();
    }

    public function test_afternoon_period_only_offers_afternoon_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_clinic_open', '08:00', 'string');
        $this->setting('social_appointment_clinic_close', '19:00', 'string');
        $this->setting('social_appointment_afternoon_start', '13:00', 'string');
        $this->setting('social_appointment_afternoon_end', '18:00', 'string');
        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $message = $this->message($comment, 'Si, tienen disponibilidad para el jueves en la tarde');

        $offer = app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment, $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => '2026-07-16',
                'preferred_time_parsed' => null,
                'preferred_period' => 'afternoon',
                'intent_type' => 'appointment_interest',
            ],
        ]);

        $this->assertInstanceOf(AppointmentSlotOffer::class, $offer);
        foreach ($offer->metadata['options'] as $option) {
            $slot = Carbon::parse($option['datetime']);
            $this->assertGreaterThanOrEqual(13, $slot->hour);
            $this->assertLessThan(18, $slot->hour + ($slot->minute / 60));
        }

        Carbon::setTestNow();
    }

    public function test_full_option_label_confirms_selection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $offer = $this->createOffer($comment, '2026-07-15');
        $option = $offer->metadata['options'][2];
        $slot = Carbon::parse($option['datetime']);
        $message = $this->message($comment, $slot->isoFormat('dddd D [de] MMMM').' - '.$slot->format('g:i A'));

        $result = app(AppointmentSlotOfferService::class)->handleSelection($comment, $message);

        $this->assertNotNull($result);
        $this->assertSame($option['datetime'], $result['appointment']->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame('selected', $offer->refresh()->status);
        $this->assertSame($option['index'], $offer->selected_option_index);

        Carbon::setTestNow();
    }

    public function test_numeric_selection_can_confirm_options_after_third(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_max_slots_offer', 6, 'integer');
        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $offer = $this->createOffer($comment, '2026-07-15');
        $option = $offer->metadata['options'][4];
        $message = $this->message($comment, '5');

        $result = app(AppointmentSlotOfferService::class)->handleSelection($comment, $message);

        $this->assertNotNull($result);
        $this->assertSame($option['datetime'], $result['appointment']->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(5, $offer->refresh()->selected_option_index);

        Carbon::setTestNow();
    }

    public function test_offer_without_suggested_doctor_uses_fallback_doctor_for_link_options(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_max_slots_offer', 3, 'integer');
        $procedure = Procedure::factory()->create(['name' => 'Valoracion dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Fallback']);
        $comment = $this->socialComment($procedure, $doctor);
        $comment->update(['suggested_doctor_id' => null]);
        $message = $this->message($comment->refresh(), 'Quiero una cita');

        $offer = app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment->refresh(), $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => null,
                'preferred_time_parsed' => null,
                'preferred_period' => null,
                'intent_type' => 'appointment_interest',
            ],
        ]);

        $this->assertNotNull($offer);
        $this->assertNotEmpty($offer->metadata['options']);
        $this->assertSame($doctor->id, $offer->metadata['options'][0]['doctor_id']);
        $this->assertNotEmpty(app(AppointmentSlotOfferService::class)->validOptionsForOffer($offer));

        $metadata = $offer->metadata;
        $metadata['options'][0]['doctor_id'] = null;
        $offer->update(['metadata' => $metadata]);

        $this->assertNotEmpty(app(AppointmentSlotOfferService::class)->validOptionsForOffer($offer->refresh()));

        Carbon::setTestNow();
    }

    public function test_confirming_option_creates_patient_when_lead_has_phone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['phone' => '+1 (555) 0001', 'display_name' => 'Maria WhatsApp']);

        $offer = $this->createOffer($comment, '2026-07-18');
        $appointment = app(AppointmentSlotOfferService::class)->confirmFromToken($offer, 1);

        $this->assertNotNull($appointment->patient_id);
        $this->assertDatabaseHas('patients', [
            'id' => $appointment->patient_id,
            'full_name' => 'Maria WhatsApp',
            'phone' => '+1 (555) 0001',
        ]);
        $this->assertSame($appointment->patient_id, $comment->refresh()->converted_patient_id);

        Carbon::setTestNow();
    }

    public function test_confirming_option_reuses_existing_patient_by_phone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $patient = Patient::factory()->create([
            'full_name' => 'Paciente Existente',
            'phone' => '+15550001',
        ]);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['phone' => '1 555 0001']);

        $offer = $this->createOffer($comment, '2026-07-19');
        $appointment = app(AppointmentSlotOfferService::class)->confirmFromToken($offer, 1);

        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame(1, Patient::where('phone', '+15550001')->count());
        $this->assertSame($patient->id, $comment->refresh()->converted_patient_id);

        Carbon::setTestNow();
    }

    public function test_confirming_option_does_not_create_patient_without_required_phone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);
        $comment = $this->socialComment($procedure, $doctor);

        $offer = $this->createOffer($comment, '2026-07-20');
        $appointment = app(AppointmentSlotOfferService::class)->confirmFromToken($offer, 1);

        $this->assertNull($appointment->patient_id);
        $this->assertDatabaseCount('patients', 0);

        Carbon::setTestNow();
    }

    public function test_whatsapp_first_lead_selection_asks_for_name(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 30, 'integer');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['display_name' => '+15550001']);

        $offer = $this->createOffer($comment, '2026-07-15');
        $message = $this->message($comment, '1');

        $result = app(AppointmentSlotOfferService::class)->handleSelection($comment, $message);

        $this->assertNotNull($result);
        $this->assertTrue($result['pending_patient_info'] ?? false);
        $this->assertSame('awaiting_name', $offer->refresh()->metadata['patient_info_state'] ?? null);
        $this->assertSame(1, $offer->metadata['pending_option_index']);
        $this->assertStringContainsString('a nombre', $result['reply']);

        Carbon::setTestNow();
    }

    public function test_handle_patient_name_reply_saves_name_and_asks_for_phone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 30, 'integer');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['display_name' => '+15550001', 'phone' => '+1 555 0001']);

        $offer = $this->createOffer($comment, '2026-07-15');
        $offer->update(['metadata' => array_merge($offer->metadata, [
            'pending_option_index' => 2,
            'patient_info_state' => 'awaiting_name',
        ])]);
        $nameMessage = $this->message($comment, 'Juan Constantine');

        $result = app(AppointmentSlotOfferService::class)->handlePatientInfoReply($offer->refresh(), $comment, $nameMessage);

        $this->assertNotNull($result);
        $this->assertTrue($result['pending_patient_info'] ?? false);
        $this->assertSame('Juan Constantine', $comment->fresh()->socialIdentity->display_name);
        $this->assertSame('awaiting_phone', $offer->refresh()->metadata['patient_info_state'] ?? null);
        $this->assertStringContainsString('perfecto', mb_strtolower($result['reply']));
        $this->assertStringContainsString('555', $result['reply']);

        Carbon::setTestNow();
    }

    public function test_handle_phone_confirmation_creates_appointment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 30, 'integer');
        $this->setting('social_appointment_slot_hold_minutes', 10, 'integer');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['display_name' => 'Juan Constantine', 'phone' => '+1 555 0001']);

        $offer = $this->createOffer($comment, '2026-07-15');
        $offer->update(['metadata' => array_merge($offer->metadata, [
            'pending_option_index' => 1,
            'patient_info_state' => 'awaiting_phone',
        ])]);
        $confirmMessage = $this->message($comment, 'Sí');

        $result = app(AppointmentSlotOfferService::class)->handlePatientInfoReply($offer->refresh(), $comment, $confirmMessage);

        $this->assertNotNull($result);
        $this->assertArrayNotHasKey('pending_patient_info', $result);
        $this->assertNotNull($result['appointment']);
        $this->assertSame('selected', $offer->refresh()->status);
        $this->assertSame(1, $offer->selected_option_index);
        $this->assertArrayNotHasKey('patient_info_state', $offer->metadata);

        Carbon::setTestNow();
    }

    public function test_full_whatsapp_flow_with_patient_info_collection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 60, 'integer');
        $this->setting('social_appointment_slot_hold_minutes', 10, 'integer');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['display_name' => '+15550001', 'phone' => '+1 555 0001']);

        $offer = $this->createOffer($comment, '2026-07-16');

        $selectMessage = $this->message($comment, '2');
        $selectResult = app(AppointmentSlotOfferService::class)->handleSelection($comment, $selectMessage);

        $this->assertNotNull($selectResult);
        $this->assertTrue($selectResult['pending_patient_info'] ?? false);
        $this->assertSame('awaiting_name', $offer->refresh()->metadata['patient_info_state']);
        $this->assertSame(2, $offer->metadata['pending_option_index']);

        $nameMessage = $this->message($comment, 'Maria Perez');
        $nameResult = app(AppointmentSlotOfferService::class)->handlePatientInfoReply($offer->refresh(), $comment, $nameMessage);

        $this->assertNotNull($nameResult);
        $this->assertTrue($nameResult['pending_patient_info'] ?? false);
        $this->assertSame('Maria Perez', $comment->fresh()->socialIdentity->display_name);
        $this->assertSame('awaiting_phone', $offer->refresh()->metadata['patient_info_state']);

        $phoneMessage = $this->message($comment, 'Sí');
        $phoneResult = app(AppointmentSlotOfferService::class)->handlePatientInfoReply($offer->refresh(), $comment, $phoneMessage);

        $this->assertNotNull($phoneResult);
        $this->assertArrayNotHasKey('pending_patient_info', $phoneResult);
        $this->assertNotNull($phoneResult['appointment']);
        $this->assertSame('selected', $offer->refresh()->status);
        $this->assertSame(2, $offer->selected_option_index);

        Carbon::setTestNow();
    }

    public function test_lead_with_real_name_skips_patient_info_and_confirms_directly(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_propose_slots', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_offer_link_minutes', 30, 'integer');
        $this->setting('social_appointment_slot_hold_minutes', 10, 'integer');
        $this->setting('social_appointment_auto_create_patient', true, 'boolean');
        $this->setting('social_appointment_require_whatsapp_phone_for_patient', true, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Agenda']);

        $comment = $this->socialComment($procedure, $doctor);
        $comment->socialIdentity->update(['display_name' => 'Carlos Ruiz', 'phone' => '+1 555 0001']);

        $offer = $this->createOffer($comment, '2026-07-15');
        $message = $this->message($comment, '1');

        $result = app(AppointmentSlotOfferService::class)->handleSelection($comment, $message);

        $this->assertNotNull($result);
        $this->assertArrayNotHasKey('pending_patient_info', $result);
        $this->assertNotNull($result['appointment']);
        $this->assertSame('selected', $offer->refresh()->status);

        Carbon::setTestNow();
    }

    private function setting(string $key, mixed $value, string $type): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'value_type' => $type, 'label' => $key, 'is_active' => true],
        );
    }

    private function createOffer(SocialComment $comment, string $date): AppointmentSlotOffer
    {
        $message = $this->message($comment, 'Quiero una cita');

        return app(AppointmentSlotOfferService::class)->createFromAgentResponse($comment, $message, [
            'intent' => 'appointment_interest',
            'appointment_candidate' => [
                'wants_appointment' => true,
                'preferred_date_parsed' => $date,
                'preferred_time_parsed' => null,
                'preferred_period' => 'afternoon',
                'intent_type' => 'appointment_interest',
            ],
        ]);
    }

    private function socialComment(Procedure $procedure, Professional $doctor): SocialComment
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
            'suggested_doctor_id' => $doctor->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_' . uniqid(),
            'author_name' => 'Test User',
            'author_username' => 'test_user',
            'comment_text' => 'Quiero agendar',
            'tracking_token' => 'DNT-TEST' . uniqid(),
        ]);
    }

    private function message(SocialComment $comment, string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'social_comment_id' => $comment->id,
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => '+15550001',
            'to_phone' => '+15550002',
            'message_body' => $body,
            'message_sid' => 'wamid.' . uniqid(),
        ]);
    }
}
