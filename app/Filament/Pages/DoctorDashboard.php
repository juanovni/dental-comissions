<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Professional;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class DoctorDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?string $navigationLabel = 'Doctor';

    protected static ?string $title = 'Dashboard - Doctor';

    protected static ?string $slug = 'doctor-dashboard';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.doctor-dashboard';

    #[Computed]
    public function doctors(): Collection
    {
        return Professional::query()
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Professional $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'next' => $this->nextPatientForDoctor($d->id),
                'queue' => $this->queueForDoctor($d->id),
            ]);
    }

    private function nextPatientForDoctor(int $doctorId): ?array
    {
        $appointment = Appointment::query()
            ->with(['patient', 'procedure'])
            ->where('doctor_id', $doctorId)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [
                AppointmentStatus::Waiting,
                AppointmentStatus::OnTheWay,
                AppointmentStatus::Confirmed,
            ])
            ->orderBy('scheduled_at')
            ->first();

        if (! $appointment) {
            return null;
        }

        return [
            'id' => $appointment->id,
            'patient_name' => $appointment->patient?->full_name ?? '--',
            'procedure_name' => $appointment->procedure?->name ?? '--',
            'scheduled_time' => $appointment->scheduled_at?->format('H:i'),
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'status_color' => $appointment->status->color(),
            'waiting_minutes' => $appointment->waitingTime(),
            'room' => $appointment->room,
        ];
    }

    private function queueForDoctor(int $doctorId): Collection
    {
        return Appointment::query()
            ->with(['patient', 'procedure'])
            ->where('doctor_id', $doctorId)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [
                AppointmentStatus::Confirmed,
                AppointmentStatus::OnTheWay,
                AppointmentStatus::Waiting,
            ])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $a): array => [
                'id' => $a->id,
                'patient_name' => $a->patient?->full_name ?? '--',
                'procedure_name' => $a->procedure?->name ?? '--',
                'scheduled_time' => $a->scheduled_at?->format('H:i'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'waiting_minutes' => $a->waitingTime(),
            ]);
    }
}
