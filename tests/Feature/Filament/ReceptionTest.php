<?php

namespace Tests\Feature\Filament;

use App\Enums\AppointmentStatus;
use App\Filament\Pages\Reception;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_page_shows_today_appointments(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10)->setMinute(0),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Reception::class)
            ->assertSee('Por llegar')
            ->assertSee($appointment->patient->full_name);
    }

    public function test_reception_page_can_check_in_patient(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10)->setMinute(0),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Reception::class)
            ->call('transition', $appointment->id, AppointmentStatus::CheckedIn->value);

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertSame('reception', $appointment->check_in_source);
    }

    public function test_reception_page_can_save_operational_note(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->setHour(10)->setMinute(0),
            'status' => AppointmentStatus::CheckedIn,
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reception::class)
            ->call('openNoteModal', $appointment->id)
            ->set('noteText', 'Llego con acompanante.')
            ->call('saveNote');

        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'created_by' => $user->id,
            'note_type' => 'reception',
            'note' => 'Llego con acompanante.',
        ]);
    }
}
