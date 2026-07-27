<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use App\Services\AppointmentReminderResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentReminderResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_confirm_appointment_from_whatsapp_reminder(): void
    {
        $patient = Patient::factory()->create(['phone' => '+593985925100']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay(),
            'status' => AppointmentStatus::Scheduled,
        ]);

        AppointmentReminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'whatsapp',
            'reminder_type' => 'first',
            'status' => 'sent',
            'to_phone' => '593985925100',
            'message' => 'Recordatorio de cita.',
            'scheduled_for' => $appointment->scheduled_at,
            'sent_at' => now(),
        ]);

        $message = $this->incomingMessage('593985925100', 'Confirmo');

        $result = app(AppointmentReminderResponseService::class)->handle($message);

        $this->assertSame('confirmed', $result['action']);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->refresh()->status);
        $this->assertSame(WhatsappMessageStatus::Confirmed, $message->refresh()->status);
    }

    public function test_reschedule_response_creates_operational_note_for_review(): void
    {
        $patient = Patient::factory()->create(['phone' => '+593985925100']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay(),
            'status' => AppointmentStatus::Scheduled,
        ]);

        AppointmentReminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'whatsapp',
            'reminder_type' => 'first',
            'status' => 'sent',
            'to_phone' => '593985925100',
            'message' => 'Recordatorio de cita.',
            'scheduled_for' => $appointment->scheduled_at,
            'sent_at' => now(),
        ]);

        $message = $this->incomingMessage('593985925100', 'Necesito reprogramar');

        $result = app(AppointmentReminderResponseService::class)->handle($message);

        $this->assertSame('needs_review', $result['action']);
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->refresh()->status);
        $this->assertSame(WhatsappMessageStatus::NeedsReview, $message->refresh()->status);
        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'note_type' => 'whatsapp',
        ]);
    }

    private function incomingMessage(string $fromPhone, string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => $fromPhone,
            'to_phone' => 'clinic',
            'message_body' => $body,
            'message_sid' => fake()->uuid(),
        ]);
    }
}
