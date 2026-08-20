<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentFlowService;
use App\Services\SocialCrmSettingsService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Reception extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Operación Clinica';

    protected static ?string $navigationLabel = 'Recepcion';

    protected static ?string $title = 'Recepcion';

    protected static ?string $slug = 'reception';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.reception';

    public string $search = '';

    public ?int $selectedAppointmentId = null;

    public ?int $noteAppointmentId = null;

    public string $noteText = '';

    public bool $showAlerts = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_reception.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'arriving' => 'Por llegar',
            'waiting' => 'En espera',
            'preparing' => 'En preparacion',
            'ready' => 'Listo para doctor',
            'in_consultation' => 'En consulta',
        ];
    }

    public function cards(string $column): Collection
    {
        return $this->baseQuery()
            ->when($column === 'arriving', fn (Builder $query): Builder => $query
                ->whereIn('status', [
                    AppointmentStatus::PendingConfirmation->value,
                    AppointmentStatus::Scheduled->value,
                    AppointmentStatus::Confirmed->value,
                    AppointmentStatus::Rescheduled->value,
                ])
                ->whereNull('checked_in_at'))
            ->when($column === 'waiting', fn (Builder $query): Builder => $query->where('status', AppointmentStatus::CheckedIn->value))
            ->when($column === 'preparing', fn (Builder $query): Builder => $query->where('status', AppointmentStatus::Preparing->value))
            ->when($column === 'ready', fn (Builder $query): Builder => $query->where('status', AppointmentStatus::ReadyForDoctor->value))
            ->when($column === 'in_consultation', fn (Builder $query): Builder => $query->where('status', AppointmentStatus::InConsultation->value))
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'arriving' => $this->cards('arriving')->count(),
            'waiting' => $this->cards('waiting')->count(),
            'overdue' => $this->overdueAppointments()->count(),
            'in_consultation' => $this->cards('in_consultation')->count(),
            'no_show' => $this->baseQuery()->where('status', AppointmentStatus::NoShow->value)->count(),
        ];
    }

    public function alerts(): Collection
    {
        $settings = app(SocialCrmSettingsService::class);
        $warningMinutes = $settings->patientFlowWaitWarningMinutes();
        $criticalMinutes = $settings->patientFlowWaitCriticalMinutes();

        return collect()
            ->merge($this->cards('waiting')
                ->filter(fn (Appointment $appointment): bool => ($appointment->waitingMinutes() ?? 0) >= $warningMinutes)
                ->map(fn (Appointment $appointment): array => [
                    'id' => $appointment->id,
                    'level' => ($appointment->waitingMinutes() ?? 0) >= $criticalMinutes ? 'critical' : 'warning',
                    'patient' => $appointment->patient?->full_name ?? 'Paciente',
                    'message' => ($appointment->patient?->full_name ?? 'Paciente').' lleva '.$appointment->waitingMinutes().' min en espera',
                    'procedure' => $appointment->procedure?->name ?? 'Sin procedimiento',
                    'doctor' => $appointment->doctor?->name ?? 'Sin doctor',
                    'column' => 'En espera',
                    'column_status' => AppointmentStatus::CheckedIn->value,
                    'minutes' => $appointment->waitingMinutes() ?? 0,
                ]))
            ->merge($this->overdueAppointments()
                ->map(fn (Appointment $appointment): array => [
                    'id' => $appointment->id,
                    'level' => 'neutral',
                    'patient' => $appointment->patient?->full_name ?? 'Paciente',
                    'message' => 'La cita de '.($appointment->patient?->full_name ?? 'Paciente').' tiene retraso',
                    'procedure' => $appointment->procedure?->name ?? 'Sin procedimiento',
                    'doctor' => $appointment->doctor?->name ?? 'Sin doctor',
                    'column' => 'Por llegar',
                    'column_status' => 'arriving',
                    'minutes' => $this->overdueMinutes($appointment),
                ]))
            ->merge($this->whatsappReviewAppointments()
                ->map(function (Appointment $appointment): array {
                    $note = $appointment->appointmentNotes->first();

                    return [
                        'id' => $appointment->id,
                        'level' => 'warning',
                        'patient' => $appointment->patient?->full_name ?? 'Paciente',
                        'message' => $note?->note_type === 'whatsapp_no_response'
                            ? ($appointment->patient?->full_name ?? 'Paciente').' no respondió el recordatorio WhatsApp'
                            : ($appointment->patient?->full_name ?? 'Paciente').' pidio revisar su cita por WhatsApp',
                        'procedure' => $appointment->procedure?->name ?? 'Sin procedimiento',
                        'doctor' => $appointment->doctor?->name ?? 'Sin doctor',
                        'column' => $this->columnLabelForAppointment($appointment),
                        'column_status' => $appointment->status->value,
                        'minutes' => (int) ($note?->created_at?->diffInMinutes(now()) ?? 0),
                    ];
                }))
            ->sortByDesc(fn (array $alert): int => $alert['minutes'])
            ->values();
    }

    public function alertSummary(): array
    {
        $alerts = $this->alerts();

        return [
            'critical' => $alerts->where('level', 'critical')->count(),
            'warning' => $alerts->where('level', 'warning')->count(),
        ];
    }

    public function toggleAlerts(): void
    {
        $this->showAlerts = ! $this->showAlerts;
    }

    public function focusAppointment(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
        $this->dispatch('reception-focus-appointment', appointmentId: $appointmentId);
    }

    public function selectAppointment(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
    }

    public function closeDetail(): void
    {
        $this->selectedAppointmentId = null;
    }

    public function selectedAppointment(): ?Appointment
    {
        if (! $this->selectedAppointmentId) {
            return null;
        }

        return Appointment::query()
            ->forCurrentTenant()
            ->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote'])
            ->find($this->selectedAppointmentId);
    }

    public function transition(int $appointmentId, string $status): void
    {
        $appointment = Appointment::query()->forCurrentTenant()->findOrFail($appointmentId);

        try {
            app(AppointmentFlowService::class)->transition(
                $appointment,
                AppointmentStatus::from($status),
                'reception',
                auth()->id(),
                ['check_in_source' => 'reception'],
            );

            Notification::make()->title('Estado actualizado')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo actualizar')->body($e->getMessage())->danger()->send();
        }
    }

    public function availableTransitions(Appointment $appointment): array
    {
        $allowed = app(AppointmentFlowService::class)->allowedTransitions()[$appointment->status->value] ?? [];

        return collect($allowed)
            ->mapWithKeys(fn (AppointmentStatus $status): array => [$status->value => $this->actionLabel($status)])
            ->only([
                AppointmentStatus::CheckedIn->value,
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::InConsultation->value,
                AppointmentStatus::Completed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->all();
    }

    public function appointmentUrl(Appointment $appointment): string
    {
        return AppointmentResource::getUrl('view', ['record' => $appointment]);
    }

    public function openNoteModal(int $appointmentId): void
    {
        $this->noteAppointmentId = $appointmentId;
        $this->noteText = '';
    }

    public function closeNoteModal(): void
    {
        $this->noteAppointmentId = null;
        $this->noteText = '';
    }

    public function saveNote(): void
    {
        $this->validate([
            'noteText' => ['required', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::query()->forCurrentTenant()->findOrFail($this->noteAppointmentId);

        $appointment->appointmentNotes()->create([
            'patient_id' => $appointment->patient_id,
            'created_by' => auth()->id(),
            'visibility' => 'internal',
            'note_type' => 'reception',
            'note' => $this->noteText,
        ]);

        $this->closeNoteModal();
        Notification::make()->title('Nota guardada')->success()->send();
    }

    private function actionLabel(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::CheckedIn => 'Marcar en espera',
            AppointmentStatus::Preparing => 'Preparar paciente',
            AppointmentStatus::ReadyForDoctor => 'Listo para doctor',
            AppointmentStatus::InConsultation => 'Iniciar consulta',
            AppointmentStatus::Completed => 'Finalizar consulta',
            AppointmentStatus::Cancelled => 'Cancelar',
            AppointmentStatus::NoShow => 'No Show',
            default => $status->label(),
        };
    }

    private function baseQuery(): Builder
    {
        $todayStart = Carbon::now(app(SocialCrmSettingsService::class)->clinicTimezone())->startOfDay();
        $todayEnd = $todayStart->copy()->endOfDay();

        return Appointment::query()
            ->forCurrentTenant()
            ->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote'])
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereHas('patient', fn (Builder $q): Builder => $q->where('full_name', 'ilike', "%{$this->search}%"))
                        ->orWhereHas('doctor', fn (Builder $q): Builder => $q->where('name', 'ilike', "%{$this->search}%"))
                        ->orWhereHas('procedure', fn (Builder $q): Builder => $q->where('name', 'ilike', "%{$this->search}%"));
                });
            });
    }

    private function overdueAppointments(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Rescheduled->value,
            ])
            ->whereNull('checked_in_at')
            ->orderBy('scheduled_at')
            ->get()
            ->filter(fn (Appointment $appointment): bool => $this->overdueMinutes($appointment) > 0)
            ->values();
    }

    private function whatsappReviewAppointments(): Collection
    {
        $noteTypes = ['whatsapp', 'whatsapp_no_response'];

        return $this->baseQuery()
            ->with(['appointmentNotes' => fn ($query) => $query
                ->whereIn('note_type', $noteTypes)
                ->where('created_at', '>=', now()->subDay())
                ->latest()])
            ->whereHas('appointmentNotes', fn (Builder $query): Builder => $query
                ->whereIn('note_type', $noteTypes)
                ->where('created_at', '>=', now()->subDay()))
            ->whereIn('status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Rescheduled->value,
                AppointmentStatus::CheckedIn->value,
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
            ])
            ->orderBy('scheduled_at')
            ->get();
    }

    private function columnLabelForAppointment(Appointment $appointment): string
    {
        return match ($appointment->status) {
            AppointmentStatus::CheckedIn => 'En espera',
            AppointmentStatus::Preparing => 'En preparacion',
            AppointmentStatus::ReadyForDoctor => 'Listo para doctor',
            AppointmentStatus::InConsultation => 'En consulta',
            default => 'Por llegar',
        };
    }

    private function overdueMinutes(Appointment $appointment): int
    {
        if (! $appointment->scheduled_at) {
            return 0;
        }

        $timezone = app(SocialCrmSettingsService::class)->clinicTimezone();
        $scheduledAt = Carbon::parse($appointment->scheduled_at->format('Y-m-d H:i:s'), $timezone);
        $now = Carbon::now($timezone);

        if ($scheduledAt->greaterThanOrEqualTo($now)) {
            return 0;
        }

        return (int) $scheduledAt->diffInMinutes($now);
    }
}
