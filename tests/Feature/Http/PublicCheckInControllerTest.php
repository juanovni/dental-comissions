<?php

namespace Tests\Feature\Http;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCheckInControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_page_renders(): void
    {
        $this->get('/check-in/clinica')
            ->assertOk()
            ->assertSee('Confirma tu llegada');
    }

    public function test_patient_can_check_in_by_phone(): void
    {
        $this->withoutMiddleware();

        $patient = Patient::factory()->create(['phone' => '+52 555 123 4567']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->post('/check-in/clinica', ['identifier' => '+52 555 123 4567'])
            ->assertRedirect('/check-in/clinica');

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertSame('qr', $appointment->check_in_source);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'to_status' => AppointmentStatus::CheckedIn->value,
            'source' => 'qr',
        ]);
    }

    public function test_patient_can_check_in_with_country_code_when_phone_is_stored_locally(): void
    {
        $this->withoutMiddleware();

        $patient = Patient::factory()->create(['phone' => '0985925100']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->post('/check-in/clinica', ['identifier' => '+593985925100'])
            ->assertRedirect('/check-in/clinica');

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->refresh()->status);
    }

    public function test_check_in_by_code_uses_appointment_id(): void
    {
        $this->withoutMiddleware();

        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->post('/check-in/clinica', ['identifier' => (string) $appointment->id])
            ->assertRedirect('/check-in/clinica');

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->refresh()->status);
    }

    public function test_unknown_identifier_returns_not_found_message(): void
    {
        $this->withoutMiddleware();

        $this->followingRedirects()
            ->post('/check-in/clinica', ['identifier' => '0000000000'])
            ->assertSee('No encontramos tu cita');
    }

    public function test_multiple_matches_ask_patient_to_select_appointment(): void
    {
        $this->withoutMiddleware();

        $patient = Patient::factory()->create(['phone' => '+52 555 123 4567']);
        Appointment::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->followingRedirects()
            ->post('/check-in/clinica', ['identifier' => '+52 555 123 4567'])
            ->assertSee('Selecciona tu cita');
    }
}
