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

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_assistant.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Pacientes en espera, preparacion y listos para doctor.';
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
        return $this->cards(AppointmentStatus::CheckedIn->value)
            ->filter(fn (Appointment $appointment): bool => ($appointment->waitingMinutes() ?? 0) >= 20)
            ->map(fn (Appointment $appointment): string => ($appointment->patient?->full_name ?? 'Paciente').' lleva '.$appointment->waitingMinutes().' min en espera')
            ->values();
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
            ? Appointment::query()->with(['patient', 'doctor', 'procedure'])->find($this->selectedAppointmentId)
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

        return collect($allowed)
            ->mapWithKeys(fn (AppointmentStatus $status): array => [$status->value => match ($status) {
                AppointmentStatus::Preparing => 'Preparar paciente',
                AppointmentStatus::ReadyForDoctor => 'Listo para doctor',
                AppointmentStatus::InConsultation => 'Iniciar consulta',
                AppointmentStatus::CheckedIn => 'Volver a espera',
                default => $status->label(),
            }])
            ->only([
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::CheckedIn->value,
            ])
            ->all();
    }

    private function baseQuery(): Builder
    {
        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure'])
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
}
