<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_updates_status_timestamps_and_event(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
        ]);

        app(AppointmentFlowService::class)->transition(
            $appointment,
            AppointmentStatus::CheckedIn,
            'reception',
            metadata: ['check_in_source' => 'reception'],
        );

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertSame('reception', $appointment->check_in_source);

        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'event_type' => 'status_changed',
            'from_status' => AppointmentStatus::Confirmed->value,
            'to_status' => AppointmentStatus::CheckedIn->value,
            'source' => 'reception',
        ]);
    }

    public function test_patient_flow_transitions_to_consultation_and_completed(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::CheckedIn,
            'checked_in_at' => now()->subMinutes(20),
        ]);

        $flow = app(AppointmentFlowService::class);

        $flow->transition($appointment, AppointmentStatus::Preparing, 'assistant');
        $flow->transition($appointment, AppointmentStatus::ReadyForDoctor, 'assistant');
        $flow->transition($appointment, AppointmentStatus::InConsultation, 'doctor');
        $flow->transition($appointment, AppointmentStatus::Completed, 'doctor');

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::Completed, $appointment->status);
        $this->assertNotNull($appointment->preparation_started_at);
        $this->assertNotNull($appointment->ready_for_doctor_at);
        $this->assertNotNull($appointment->consultation_started_at);
        $this->assertNotNull($appointment->consultation_finished_at);
        $this->assertNotNull($appointment->completed_at);
        $this->assertCount(4, $appointment->events);
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Completed,
        ]);

        $this->expectException(ValidationException::class);

        app(AppointmentFlowService::class)->transition(
            $appointment,
            AppointmentStatus::CheckedIn,
            'reception',
        );
    }
}
