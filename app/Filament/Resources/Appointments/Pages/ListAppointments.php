<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Professional;
use App\Services\AppointmentWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.resources.appointments.pages.list-appointments';

    public string $period = 'all';

    public string $search = '';

    public ?string $statusFilter = null;

    public ?int $doctorFilter = null;

    public ?int $patientFilter = null;

    public bool $showRescheduleModal = false;

    public ?int $reschedulingAppointmentId = null;

    public ?string $newScheduledAt = null;

    public ?int $newDurationMinutes = null;

    public bool $showNoShowModal = false;

    public ?int $noShowAppointmentId = null;

    public ?string $noShowNotes = null;

    public bool $showCancelModal = false;

    public ?int $cancelAppointmentId = null;

    public ?string $cancelReason = null;

    public ?int $selectedAppointmentId = null;

    public function getTitle(): string
    {
        return 'Citas';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubheading(): HtmlString
    {
        return new HtmlString('<span class="text-sm font-normal text-muted-foreground">Agenda, reprograma y da seguimiento a las citas de tus pacientes.</span>');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear cita')
                ->icon('heroicon-o-plus')
                ->createAnother(false)
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label('Crear cita')
                    ->icon('heroicon-o-calendar-days')),
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'search', 'statusFilter', 'doctorFilter', 'patientFilter'], true)) {
            $this->resetPage();
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'statusFilter', 'doctorFilter', 'patientFilter');
    }

    public function openRescheduleModal(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->reschedulingAppointmentId = $appointmentId;
        $this->newScheduledAt = $appointment->scheduled_at?->format('Y-m-d\TH:i');
        $this->newDurationMinutes = $appointment->duration_minutes ?? 45;
        $this->showRescheduleModal = true;
    }

    public function closeRescheduleModal(): void
    {
        $this->reset('showRescheduleModal', 'reschedulingAppointmentId', 'newScheduledAt', 'newDurationMinutes');
    }

    public function saveReschedule(): void
    {
        $this->validate([
            'newScheduledAt' => 'required',
            'newDurationMinutes' => 'required|integer|min:1',
        ]);

        $newDate = Carbon::parse($this->newScheduledAt);
        $duration = (int) $this->newDurationMinutes;

        if ($this->hasRescheduleConflict($this->reschedulingAppointmentId, $newDate, $duration)) {
            $this->addError('newScheduledAt', 'Ya existe una cita agendada en este horario. Selecciona una fecha y hora diferente.');
            return;
        }

        try {
            $appointment = Appointment::query()->findOrFail($this->reschedulingAppointmentId);
            app(AppointmentWorkflowService::class)->reschedule($appointment, $newDate, $duration);
            Notification::make()->title('Cita reprogramada exitosamente')->success()->send();
            $this->closeRescheduleModal();
        } catch (\Throwable $e) {
            Notification::make()->title('Error al reprogramar')->body($e->getMessage())->danger()->send();
        }
    }

    private function hasRescheduleConflict(?int $appointmentId, Carbon $newStart, int $durationMinutes): bool
    {
        $appointment = Appointment::query()->find($appointmentId);

        if (!$appointment || !$appointment->doctor_id) {
            return false;
        }

        $newEnd = (clone $newStart)->addMinutes($durationMinutes);

        return Appointment::query()
            ->where('id', '!=', $appointmentId)
            ->where('doctor_id', $appointment->doctor_id)
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled,
                AppointmentStatus::Completed,
                AppointmentStatus::NoShow,
            ])
            ->get()
            ->contains(function (Appointment $existing) use ($newStart, $newEnd) {
                $existingStart = $existing->scheduled_at;
                $existingEnd = (clone $existingStart)->addMinutes($existing->duration_minutes ?? 0);

                return $existingStart->lessThan($newEnd) && $existingEnd->greaterThan($newStart);
            });
    }

    public function completeAppointment(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            app(AppointmentWorkflowService::class)->complete($appointment);
            Notification::make()->title('Cita marcada como completada')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function openNoShowModal(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->noShowAppointmentId = $appointmentId;
        $this->noShowNotes = '';
        $this->showNoShowModal = true;
    }

    public function closeNoShowModal(): void
    {
        $this->reset('showNoShowModal', 'noShowAppointmentId', 'noShowNotes');
    }

    public function saveNoShow(): void
    {
        $appointment = Appointment::query()->findOrFail($this->noShowAppointmentId);

        try {
            app(AppointmentWorkflowService::class)->markNoShow($appointment);
            Notification::make()->title('Marcada como no asistio')->success()->send();
            $this->closeNoShowModal();
        } catch (\Throwable $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function openCancelModal(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);
        $this->cancelAppointmentId = $appointmentId;
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->reset('showCancelModal', 'cancelAppointmentId', 'cancelReason');
    }

    public function saveCancel(): void
    {
        $appointment = Appointment::query()->findOrFail($this->cancelAppointmentId);

        try {
            app(AppointmentWorkflowService::class)->cancel($appointment, $this->cancelReason ?: null);
            Notification::make()->title('Cita cancelada')->success()->send();
            $this->closeCancelModal();
        } catch (\Throwable $e) {
            Notification::make()->title('Error al cancelar')->body($e->getMessage())->danger()->send();
        }
    }

    public function selectAppointment(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
    }

    public function closeAppointmentDrawer(): void
    {
        $this->selectedAppointmentId = null;
    }

    public function getSelectedAppointmentProperty(): ?Appointment
    {
        if (!$this->selectedAppointmentId) {
            return null;
        }

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure', 'socialComment', 'socialComment.socialIdentity'])
            ->find($this->selectedAppointmentId);
    }

    public function getGroupedAppointmentsProperty(): array
    {
        return $this->appointmentsQuery()
            ->get()
            ->groupBy(fn (Appointment $appointment): string => $appointment->scheduled_at->toDateString())
            ->map(fn ($appointments, string $date): array => [
                'date' => Carbon::parse($date),
                'label' => strtoupper(Carbon::parse($date)->locale('es')->isoFormat('ddd D [de] MMM')),
                'count' => $appointments->count(),
                'appointments' => $appointments,
            ])
            ->values()
            ->all();
    }

    public function getAppointmentsCountProperty(): int
    {
        return $this->appointmentsQuery()->count();
    }

    public function getStatusOptionsProperty(): array
    {
        return [
            AppointmentStatus::Confirmed->value => 'Confirmada',
            AppointmentStatus::PendingConfirmation->value => 'Pendiente',
            AppointmentStatus::Rescheduled->value => 'Reprogramada',
            AppointmentStatus::Cancelled->value => 'Cancelada',
            AppointmentStatus::Completed->value => 'Completada',
            AppointmentStatus::NoShow->value => 'No asistio',
        ];
    }

    public function getDoctorOptionsProperty(): array
    {
        return Professional::query()
            ->where('role', 'doctor')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getPatientOptionsProperty(): array
    {
        return Patient::query()
            ->orderBy('full_name')
            ->limit(100)
            ->pluck('full_name', 'id')
            ->all();
    }

    public function statusLabel(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::PendingConfirmation => 'Pendiente',
            AppointmentStatus::Scheduled => 'Agendada',
            AppointmentStatus::Confirmed => 'Confirmada',
            AppointmentStatus::Rescheduled => 'Reprogramada',
            AppointmentStatus::Cancelled => 'Cancelada',
            AppointmentStatus::Completed => 'Completada',
            AppointmentStatus::NoShow => 'No asistio',
        };
    }

    public function canUpdateStatus(Appointment $appointment): bool
    {
        return in_array($appointment->status, [
            AppointmentStatus::Confirmed,
            AppointmentStatus::Scheduled,
            AppointmentStatus::Rescheduled,
        ], true);
    }

    protected function appointmentsQuery(): Builder
    {
        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure', 'socialComment', 'socialComment.socialIdentity'])
            ->when($this->period === 'today', fn (Builder $query): Builder => $query->whereDate('scheduled_at', today()))
            ->when($this->period === 'upcoming', fn (Builder $query): Builder => $query->where('scheduled_at', '>=', now()->startOfDay()))
            ->when($this->period === 'past', fn (Builder $query): Builder => $query->where('scheduled_at', '<', now()->startOfDay()))
            ->when($this->statusFilter, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($this->doctorFilter, fn (Builder $query, int $doctorId): Builder => $query->where('doctor_id', $doctorId))
            ->when($this->patientFilter, fn (Builder $query, int $patientId): Builder => $query->where('patient_id', $patientId))
            ->when(trim($this->search) !== '', function (Builder $query): Builder {
                $search = '%' . trim($this->search) . '%';

                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('patient', fn (Builder $q): Builder => $q
                            ->where('full_name', 'like', $search)
                            ->orWhere('phone', 'like', $search))
                        ->orWhereHas('doctor', fn (Builder $q): Builder => $q->where('name', 'like', $search))
                        ->orWhereHas('procedure', fn (Builder $q): Builder => $q->where('name', 'like', $search));
                });
            })
            ->orderBy('scheduled_at');
    }
}
