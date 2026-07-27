<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorAssistantAssignment;
use App\Services\AppointmentFlowService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClinicalQueue extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Operación Clinica';

    protected static ?string $navigationLabel = 'Cola clinica';

    protected static ?string $title = 'Cola clinica';

    protected static ?string $slug = 'clinical-queue';

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.clinical-queue';

    public string $search = '';

    public ?int $selectedAppointmentId = null;

    public ?int $noteAppointmentId = null;

    public string $noteText = '';

    public bool $showAlerts = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_assistant.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function columns(): array
    {
        return [
            AppointmentStatus::CheckedIn->value => 'En espera',
            AppointmentStatus::Preparing->value => 'En preparacion',
            AppointmentStatus::ReadyForDoctor->value => 'Listo para doctor',
            AppointmentStatus::InConsultation->value => 'En consulta',
        ];
    }

    public function cards(string $status): Collection
    {
        return $this->baseQuery()
            ->where('status', $status)
            ->orderBy('scheduled_at')
            ->get();
    }

    public function summary(): array
    {
        return collect($this->columns())
            ->mapWithKeys(fn (string $label, string $status): array => [$status => $this->cards($status)->count()])
            ->all();
    }

    public function alerts(): Collection
    {
        return collect([
            AppointmentStatus::CheckedIn->value,
            AppointmentStatus::Preparing->value,
            AppointmentStatus::ReadyForDoctor->value,
        ])
            ->flatMap(fn (string $status): Collection => $this->cards($status))
            ->filter(fn (Appointment $appointment): bool => ($appointment->waitingMinutes() ?? 0) >= 10)
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'level' => ($appointment->waitingMinutes() ?? 0) >= 20 ? 'critical' : 'warning',
                'patient' => $appointment->patient?->full_name ?? 'Paciente',
                'message' => ($appointment->patient?->full_name ?? 'Paciente').' lleva '.$appointment->waitingMinutes().' min '.$this->alertStatusText($appointment),
                'procedure' => $appointment->procedure?->name ?? 'Sin procedimiento',
                'doctor' => $appointment->doctor?->name ?? 'Sin doctor',
                'column' => $appointment->status->label(),
                'column_status' => $appointment->status->value,
                'minutes' => $appointment->waitingMinutes() ?? 0,
            ])
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
        $this->dispatch('cq-focus-appointment', appointmentId: $appointmentId);
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
        return $this->selectedAppointmentId
            ? Appointment::query()->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote'])->find($this->selectedAppointmentId)
            : null;
    }

    public function transition(int $appointmentId, string $status): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            app(AppointmentFlowService::class)->transition($appointment, AppointmentStatus::from($status), 'assistant', auth()->id());
            Notification::make()->title('Estado actualizado')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo actualizar')->body($e->getMessage())->danger()->send();
        }
    }

    public function availableTransitions(Appointment $appointment): array
    {
        $allowed = app(AppointmentFlowService::class)->allowedTransitions()[$appointment->status->value] ?? [];

        $labels = collect($allowed)
            ->mapWithKeys(fn (AppointmentStatus $status): array => [$status->value => match ($status) {
                AppointmentStatus::Preparing => 'Preparar paciente',
                AppointmentStatus::ReadyForDoctor => 'Listo para doctor',
                AppointmentStatus::InConsultation => 'Iniciar consulta',
                AppointmentStatus::CheckedIn => 'Volver a espera',
                default => $status->label(),
            }]);

        return collect([
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::CheckedIn->value,
            ])
            ->filter(fn (string $status): bool => $labels->has($status))
            ->mapWithKeys(fn (string $status): array => [$status => $labels->get($status)])
            ->all();
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

        $appointment = Appointment::query()->findOrFail($this->noteAppointmentId);

        $appointment->appointmentNotes()->create([
            'patient_id' => $appointment->patient_id,
            'created_by' => auth()->id(),
            'visibility' => 'clinical_team',
            'note_type' => 'assistant',
            'note' => $this->noteText,
        ]);

        $this->closeNoteModal();
        Notification::make()->title('Nota guardada')->success()->send();
    }

    private function baseQuery(): Builder
    {
        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote'])
            ->whereDate('scheduled_at', today())
            ->when($this->doctorIdsForCurrentUser() !== null, fn (Builder $query): Builder => $query->whereIn('doctor_id', $this->doctorIdsForCurrentUser()))
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereHas('patient', fn (Builder $q): Builder => $q->where('full_name', 'ilike', "%{$this->search}%"))
                        ->orWhereHas('doctor', fn (Builder $q): Builder => $q->where('name', 'ilike', "%{$this->search}%"))
                        ->orWhereHas('procedure', fn (Builder $q): Builder => $q->where('name', 'ilike', "%{$this->search}%"));
                });
            });
    }

    private function doctorIdsForCurrentUser(): ?array
    {
        $user = auth()->user();

        if (! $user || in_array($user->role->value, ['super_admin', 'admin'], true)) {
            return null;
        }

        if (! $user->professional_id) {
            return [];
        }

        return DoctorAssistantAssignment::query()
            ->where('assistant_id', $user->professional_id)
            ->where('is_active', true)
            ->pluck('doctor_id')
            ->all();
    }

    private function alertStatusText(Appointment $appointment): string
    {
        return match ($appointment->status) {
            AppointmentStatus::Preparing => 'en preparacion',
            AppointmentStatus::ReadyForDoctor => 'esperando al doctor',
            default => 'en espera',
        };
    }
}
