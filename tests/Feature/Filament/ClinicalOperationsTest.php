<?php

namespace Tests\Feature\Filament;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ClinicalOperations;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicalOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_shows_operational_metrics(): void
    {
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Marta Salinas']);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->setHour(10),
            'status' => AppointmentStatus::Completed,
            'checked_in_at' => now()->setHour(9)->setMinute(55),
            'consultation_started_at' => now()->setHour(10)->setMinute(5),
            'consultation_finished_at' => now()->setHour(10)->setMinute(35),
            'completed_at' => now()->setHour(10)->setMinute(35),
        ]);
        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->setHour(11),
            'status' => AppointmentStatus::CheckedIn,
            'checked_in_at' => now()->subMinutes(45),
        ]);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->test(ClinicalOperations::class)
            ->assertSee('Agendadas')
            ->assertSee('Atendidas')
            ->assertSee('Estado actual de la clinica')
            ->assertSee('Dra. Marta Salinas')
            ->assertSee('Tiempo de espera promedio');
    }
}
