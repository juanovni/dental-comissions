<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Services\PatientFlowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientFlowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PatientFlowService::class);
    }

    /** @test */
    public function marks_patient_on_the_way(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::Confirmed);

        $result = $this->service->markOnTheWay($appointment);

        $this->assertEquals(AppointmentStatus::OnTheWay, $result->status);
        $this->assertNotNull($result->on_the_way_at);
        $this->assertDatabaseHas("appointment_events", [
            "appointment_id" => $appointment->id,
            "event" => "patient_on_the_way",
        ]);
    }

    /** @test */
    public function checks_in_patient(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::Confirmed);

        $result = $this->service->checkIn($appointment);

        $this->assertEquals(AppointmentStatus::Waiting, $result->status);
        $this->assertNotNull($result->checked_in_at);
        $this->assertDatabaseHas("appointment_events", [
            "appointment_id" => $appointment->id,
            "event" => "patient_checked_in",
        ]);
    }

    /** @test */
    public function starts_consultation(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::Waiting);
        $appointment->update(["checked_in_at" => now()->subMinutes(10)]);

        $result = $this->service->startConsultation($appointment, "Consultorio 2");

        $this->assertEquals(AppointmentStatus::InConsultation, $result->status);
        $this->assertNotNull($result->consultation_started_at);
        $this->assertEquals("Consultorio 2", $result->room);
        $this->assertNotNull($result->waiting_time_minutes);
        $this->assertDatabaseHas("appointment_events", [
            "appointment_id" => $appointment->id,
            "event" => "consultation_started",
        ]);
    }

    /** @test */
    public function finishes_consultation(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::InConsultation);
        $appointment->update([
            "consultation_started_at" => now()->subMinutes(30),
        ]);

        $result = $this->service->finishConsultation($appointment);

        $this->assertEquals(AppointmentStatus::Completed, $result->status);
        $this->assertNotNull($result->consultation_finished_at);
        $this->assertNotNull($result->completed_at);
        $this->assertDatabaseHas("appointment_events", [
            "appointment_id" => $appointment->id,
            "event" => "consultation_finished",
        ]);
    }

    /** @test */
    public function marks_no_show(): void
    {
        $appointment = $this->createAppointment(AppointmentStatus::Confirmed);

        $result = $this->service->markNoShow($appointment, "No contesto llamada", 500);

        $this->assertEquals(AppointmentStatus::NoShow, $result->status);
        $this->assertNotNull($result->no_show_at);
        $this->assertDatabaseHas("appointment_events", [
            "appointment_id" => $appointment->id,
            "event" => "appointment_no_show",
        ]);
    }

    /** @test */
    public function calculates_operation_kpis(): void
    {
        $today = Carbon::today();

        $completed = $this->createAppointment(AppointmentStatus::Completed);
        $completed->update([
            "checked_in_at" => $today->copy()->setHour(9)->setMinute(0),
            "consultation_started_at" => $today->copy()->setHour(9)->setMinute(15),
            "consultation_finished_at" => $today->copy()->setHour(9)->setMinute(50),
            "waiting_time_minutes" => 15,
            "scheduled_at" => $today->copy()->setHour(9),
        ]);

        $noShow = $this->createAppointment(AppointmentStatus::NoShow);
        $noShow->update([
            "scheduled_at" => $today->copy()->setHour(10),
        ]);

        $kpis = $this->service->getKpis($today);

        $this->assertEquals(2, $kpis["total_citas"]);
        $this->assertEquals(1, $kpis["asistieron"]);
        $this->assertEquals(1, $kpis["no_show"]);
        $this->assertEquals(50.0, $kpis["tasa_no_show"]);
        $this->assertEquals(15, $kpis["tiempo_promedio_espera"]);
    }

    /** @test */
    public function detects_check_in_request(): void
    {
        $service = app(\App\Services\WhatsappCheckInService::class);

        $this->assertTrue($service->isCheckInRequest("Llegue"));
        $this->assertTrue($service->isCheckInRequest("Ya llegue"));
        $this->assertTrue($service->isCheckInRequest("YA LLEGUE"));
        $this->assertTrue($service->isCheckInRequest("Estoy aqui"));
        $this->assertTrue($service->isCheckInRequest("acabo de llegar"));
        $this->assertFalse($service->isCheckInRequest("Hola, quiero agendar"));
        $this->assertFalse($service->isCheckInRequest("Confirmo mi cita"));
    }

    private function createAppointment(AppointmentStatus $status): Appointment
    {
        $patient = Patient::factory()->create();
        $doctor = Professional::factory()->create(["role" => "doctor"]);

        return Appointment::create([
            "patient_id" => $patient->id,
            "doctor_id" => $doctor->id,
            "scheduled_at" => Carbon::today()->setHour(9),
            "duration_minutes" => 45,
            "status" => $status,
            "source" => AppointmentSource::AdminManual,
        ]);
    }
}
