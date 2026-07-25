<?php

namespace Tests\Feature\Filament;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Pages\DoctorQueue;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_doctor_sees_own_next_patient(): void
    {
        $doctor = Professional::factory()->doctor()->create();
        $otherDoctor = Professional::factory()->doctor()->create();

        $visible = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::ReadyForDoctor,
        ]);
        $hidden = Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::ReadyForDoctor,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Doctor,
            'professional_id' => $doctor->id,
        ]);

        Livewire::actingAs($user)
            ->test(DoctorQueue::class)
            ->assertSee($visible->patient->full_name)
            ->assertDontSee($hidden->patient->full_name);
    }

    public function test_doctor_can_start_consultation(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::ReadyForDoctor,
        ]);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->test(DoctorQueue::class)
            ->call('transition', $appointment->id, AppointmentStatus::InConsultation->value);

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::InConsultation, $appointment->status);
        $this->assertNotNull($appointment->consultation_started_at);
    }

    public function test_doctor_can_save_operational_note(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::ReadyForDoctor,
        ]);

        $user = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($user)
            ->test(DoctorQueue::class)
            ->call('openNoteModal', $appointment->id)
            ->set('noteText', 'Solicita explicacion paso a paso.')
            ->call('saveNote');

        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'note_type' => 'doctor',
            'note' => 'Solicita explicacion paso a paso.',
        ]);
    }
}
