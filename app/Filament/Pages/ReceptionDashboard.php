<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\PatientFlowService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class ReceptionDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?string $navigationLabel = 'Recepcion';

    protected static ?string $title = 'Dashboard - Recepcion';

    protected static ?string $slug = 'reception-dashboard';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reception-dashboard';

    #[Url(as: 'date')]
    public ?string $selectedDate = null;

    public function mount(): void
    {
        $this->selectedDate ??= today()->toDateString();
    }

    public function updatedSelectedDate(): void
    {
        $this->dispatch('$refresh');
    }

    public function today(): void
    {
        $this->selectedDate = today()->toDateString();
    }

    public function appointments(): Collection
    {
        $date = Carbon::parse($this->selectedDate);

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure'])
            ->whereDate('scheduled_at', $date)
            ->whereIn('status', [
                AppointmentStatus::Confirmed,
                AppointmentStatus::OnTheWay,
                AppointmentStatus::Waiting,
                AppointmentStatus::InConsultation,
            ])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $a): array => [
                'id' => $a->id,
                'patient_name' => $a->patient?->full_name ?? '--',
                'doctor_name' => $a->doctor?->name ?? '--',
                'procedure_name' => $a->procedure?->name ?? '--',
                'scheduled_at' => $a->scheduled_at,
                'scheduled_time' => $a->scheduled_at?->format('H:i'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'waiting_minutes' => $a->waitingTime(),
                'room' => $a->room,
                'is_late' => $a->isLate(),
                'late_minutes' => $a->lateMinutes(),
                'has_checked_in' => $a->checked_in_at !== null,
                'checked_in_at' => $a->checked_in_at,
            ]);
    }

    public function waitingCount(): int
    {
        return $this->appointments()->where('status', AppointmentStatus::Waiting->value)->count();
    }

    public function inConsultationCount(): int
    {
        return $this->appointments()->where('status', AppointmentStatus::InConsultation->value)->count();
    }

    public function confirmedCount(): int
    {
        return $this->appointments()->where('status', AppointmentStatus::Confirmed->value)->count();
    }

    #[On('check-in')]
    public function checkIn(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            Notification::make()->title('Cita no encontrada')->danger()->send();

            return;
        }

        app(PatientFlowService::class)->checkIn($appointment);

        Notification::make()
            ->title('Llegada registrada')
            ->body($appointment->patient?->full_name . ' - ' . $appointment->scheduled_at?->format('H:i'))
            ->success()
            ->send();
    }

    #[On('start-consultation')]
    public function startConsultation(int $appointmentId, ?string $room = null): void
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            Notification::make()->title('Cita no encontrada')->danger()->send();

            return;
        }

        app(PatientFlowService::class)->startConsultation($appointment, $room);

        Notification::make()
            ->title('Consulta iniciada')
            ->body($appointment->patient?->full_name)
            ->success()
            ->send();
    }

    #[On('finish-consultation')]
    public function finishConsultation(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            Notification::make()->title('Cita no encontrada')->danger()->send();

            return;
        }

        app(PatientFlowService::class)->finishConsultation($appointment);

        Notification::make()
            ->title('Consulta finalizada')
            ->body($appointment->patient?->full_name)
            ->success()
            ->send();
    }

    #[On('mark-on-the-way')]
    public function markOnTheWay(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            Notification::make()->title('Cita no encontrada')->danger()->send();

            return;
        }

        app(PatientFlowService::class)->markOnTheWay($appointment);

        Notification::make()
            ->title('Paciente en camino')
            ->body($appointment->patient?->full_name)
            ->success()
            ->send();
    }

    #[On('mark-no-show')]
    public function markNoShow(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            Notification::make()->title('Cita no encontrada')->danger()->send();

            return;
        }

        app(PatientFlowService::class)->markNoShow($appointment, 'Registrado desde recepcion');

        Notification::make()
            ->title('No show registrado')
            ->body($appointment->patient?->full_name)
            ->success()
            ->send();
    }
}
