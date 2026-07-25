<?php

namespace Tests\Feature\Filament;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ClinicalQueue;
use App\Models\Appointment;
use App\Models\DoctorAssistantAssignment;
use App\Models\Professional;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicalQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_assistant_sees_only_assigned_doctor_patients(): void
    {
        $assistant = Professional::factory()->assistant()->create();
        $assignedDoctor = Professional::factory()->doctor()->create();
        $otherDoctor = Professional::factory()->doctor()->create();

        DoctorAssistantAssignment::create([
            'doctor_id' => $assignedDoctor->id,
            'assistant_id' => $assistant->id,
            'is_active' => true,
        ]);

        $visible = Appointment::factory()->create([
            'doctor_id' => $assignedDoctor->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::CheckedIn,
        ]);
        $hidden = Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'scheduled_at' => now()->setHour(11),
            'status' => AppointmentStatus::CheckedIn,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Assistant,
            'professional_id' => $assistant->id,
        ]);

        Livewire::actingAs($user)
            ->test(ClinicalQueue::class)
            ->assertSee($visible->patient->full_name)
            ->assertDontSee($hidden->patient->full_name);
    }

    public function test_assistant_can_mark_patient_ready_for_doctor(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Preparing,
        ]);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->test(ClinicalQueue::class)
            ->call('transition', $appointment->id, AppointmentStatus::ReadyForDoctor->value);

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::ReadyForDoctor, $appointment->status);
        $this->assertNotNull($appointment->ready_for_doctor_at);
    }
}
