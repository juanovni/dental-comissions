<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use App\Services\AppointmentWhatsappCheckInService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppointmentWhatsappCheckInServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    public function test_arrival_intent_checks_in_single_today_appointment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 09:00:00', 'America/Guayaquil'));

        $patient = Patient::factory()->create(['phone' => '0985925100']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::parse('2026-07-30 15:45:00', 'America/Guayaquil'),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $result = app(AppointmentWhatsappCheckInService::class)->handle(
            $this->message('593985925100', 'Ya llegué'),
        );

        $this->assertNotNull($result);
        $this->assertStringContainsString('recepción fue notificada', mb_strtolower($result['reply']));
        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->refresh()->status);
        $this->assertSame('whatsapp', $appointment->check_in_source);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'from_status' => AppointmentStatus::Scheduled->value,
            'to_status' => AppointmentStatus::CheckedIn->value,
            'source' => 'whatsapp',
        ]);
    }

    public function test_arrival_intent_with_multiple_today_appointments_asks_for_selection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 09:00:00', 'America/Guayaquil'));

        $patient = Patient::factory()->create(['phone' => '0985925100']);
        $first = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::parse('2026-07-30 10:00:00', 'America/Guayaquil'),
            'status' => AppointmentStatus::Scheduled,
        ]);
        $second = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::parse('2026-07-30 15:45:00', 'America/Guayaquil'),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $result = app(AppointmentWhatsappCheckInService::class)->handle(
            $this->message('593985925100', 'Estoy en recepción'),
        );

        $this->assertNotNull($result);
        $this->assertStringContainsString('más de una cita', mb_strtolower($result['reply']));
        $this->assertStringContainsString('1.', $result['reply']);
        $this->assertStringContainsString('2.', $result['reply']);
        $this->assertSame(AppointmentStatus::Scheduled, $first->refresh()->status);
        $this->assertSame(AppointmentStatus::Confirmed, $second->refresh()->status);
    }

    public function test_pending_selection_checks_in_selected_appointment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 09:00:00', 'America/Guayaquil'));

        $patient = Patient::factory()->create(['phone' => '0985925100']);
        $first = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::parse('2026-07-30 10:00:00', 'America/Guayaquil'),
            'status' => AppointmentStatus::Scheduled,
        ]);
        $second = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => Carbon::parse('2026-07-30 15:45:00', 'America/Guayaquil'),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $service = app(AppointmentWhatsappCheckInService::class);
        $service->handle($this->message('593985925100', 'Llegué'));

        $result = $service->handle($this->message('593985925100', '2'));

        $this->assertNotNull($result);
        $this->assertStringContainsString('recepción fue notificada', mb_strtolower($result['reply']));
        $this->assertSame(AppointmentStatus::Scheduled, $first->refresh()->status);
        $this->assertSame(AppointmentStatus::CheckedIn, $second->refresh()->status);
        $this->assertSame('whatsapp', $second->check_in_source);
    }

    private function message(string $fromPhone, string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => $fromPhone,
            'to_phone' => 'clinic',
            'message_body' => $body,
            'message_sid' => uniqid('wamid.', true),
        ]);
    }
}
