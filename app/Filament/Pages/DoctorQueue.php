<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Services\AppointmentFlowService;
use App\Services\SocialCrmSettingsService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DoctorQueue extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Operación Clinica';

    protected static ?string $navigationLabel = 'Mi cola';

    protected static ?string $title = 'Mi cola de atencion';

    protected static ?string $slug = 'doctor-queue';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.doctor-queue';

    public ?int $noteAppointmentId = null;

    public string $activeQueueFilter = 'ready';

    public ?int $selectedAppointmentId = null;

    public string $noteText = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_doctor.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function nextPatient(): ?Appointment
    {
        return $this->baseQuery()
            ->whereIn('status', [
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::Preparing->value,
                AppointmentStatus::CheckedIn->value,
            ])
            ->orderByRaw("case status when 'ready_for_doctor' then 1 when 'preparing' then 2 when 'checked_in' then 3 else 4 end")
            ->orderBy('scheduled_at')
            ->first();
    }

    public function currentConsultation(): ?Appointment
    {
        return $this->baseQuery()
            ->where('status', AppointmentStatus::InConsultation->value)
            ->orderBy('consultation_started_at')
            ->first();
    }

    public function pendingPatients(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', [
                AppointmentStatus::CheckedIn->value,
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
            ])
            ->when($this->nextPatient(), fn (Builder $query): Builder => $query->whereKeyNot($this->nextPatient()->getKey()))
            ->orderBy('scheduled_at')
            ->get();
    }

    public function queueAppointments(): Collection
    {
        return $this->baseQuery()
            ->whereIn('status', $this->statusesForFilter($this->activeQueueFilter))
            ->orderByRaw("case status when 'ready_for_doctor' then 1 when 'preparing' then 2 when 'checked_in' then 3 when 'in_consultation' then 4 else 5 end")
            ->orderBy('scheduled_at')
            ->get();
    }

    public function selectedAppointment(): ?Appointment
    {
        if ($this->selectedAppointmentId) {
            $selected = $this->baseQuery()
                ->whereIn('status', $this->statusesForFilter('all'))
                ->find($this->selectedAppointmentId);

            if ($selected) {
                return $selected;
            }
        }

        return $this->queueAppointments()->first() ?? $this->nextPatient() ?? $this->currentConsultation();
    }

    public function selectQueueFilter(string $filter): void
    {
        if (! array_key_exists($filter, $this->queueFilters())) {
            return;
        }

        $this->activeQueueFilter = $filter;
        $this->selectedAppointmentId = $this->queueAppointments()->first()?->id;
    }

    public function selectAppointment(int $appointmentId): void
    {
        if (! $this->baseQuery()->whereIn('status', $this->statusesForFilter('all'))->whereKey($appointmentId)->exists()) {
            return;
        }

        $this->selectedAppointmentId = $appointmentId;
    }

    public function queueFilters(): array
    {
        return [
            'all' => 'Todos',
            'ready' => 'Listos',
            'preparing' => 'Preparando',
            'waiting' => 'En espera',
            'in_consultation' => 'En consulta',
        ];
    }

    public function summary(): array
    {
        return [
            'waiting' => $this->baseQuery()->where('status', AppointmentStatus::CheckedIn->value)->count(),
            'preparing' => $this->baseQuery()->where('status', AppointmentStatus::Preparing->value)->count(),
            'ready' => $this->baseQuery()->where('status', AppointmentStatus::ReadyForDoctor->value)->count(),
            'in_consultation' => $this->baseQuery()->where('status', AppointmentStatus::InConsultation->value)->count(),
        ];
    }

    public function countForFilter(string $filter): int
    {
        return $this->baseQuery()->whereIn('status', $this->statusesForFilter($filter))->count();
    }

    public function noteOwnerLabel(AppointmentNote $note): string
    {
        if ($note->createdBy?->name) {
            return $note->createdBy->name;
        }

        return match ($note->note_type) {
            'reception' => 'Recepcion',
            'assistant' => 'Asistente',
            'doctor' => 'Doctor',
            default => ucfirst((string) $note->note_type),
        };
    }

    public function availableTransitions(Appointment $appointment): array
    {
        $allowed = app(AppointmentFlowService::class)->allowedTransitions()[$appointment->status->value] ?? [];

        if ($appointment->status === AppointmentStatus::ReadyForDoctor) {
            $allowed = collect($allowed)
                ->filter(fn (AppointmentStatus $status): bool => $status === AppointmentStatus::InConsultation)
                ->all();
        }

        return collect($allowed)
            ->mapWithKeys(fn (AppointmentStatus $status): array => [$status->value => $this->actionLabel($status)])
            ->only([
                AppointmentStatus::Preparing->value,
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::InConsultation->value,
                AppointmentStatus::Completed->value,
            ])
            ->all();
    }

    public function transition(int $appointmentId, string $status): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        try {
            app(AppointmentFlowService::class)->transition($appointment, AppointmentStatus::from($status), 'doctor', auth()->id());
            Notification::make()->title('Estado actualizado')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo actualizar')->body($e->getMessage())->danger()->send();
        }
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
            'note_type' => 'doctor',
            'note' => $this->noteText,
        ]);

        $this->closeNoteModal();
        Notification::make()->title('Nota guardada')->success()->send();
    }

    private function baseQuery(): Builder
    {
        $todayStart = Carbon::now(app(SocialCrmSettingsService::class)->clinicTimezone())->startOfDay();
        $todayEnd = $todayStart->copy()->endOfDay();

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote', 'appointmentNotes.createdBy'])
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->when($this->doctorIdForCurrentUser(), fn (Builder $query, int $doctorId): Builder => $query->where('doctor_id', $doctorId));
    }

    private function actionLabel(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Preparing => 'Preparar paciente',
            AppointmentStatus::ReadyForDoctor => 'Listo para doctor',
            AppointmentStatus::InConsultation => 'Iniciar consulta',
            AppointmentStatus::Completed => 'Finalizar consulta',
            default => $status->label(),
        };
    }

    private function statusesForFilter(string $filter): array
    {
        return match ($filter) {
            'ready' => [AppointmentStatus::ReadyForDoctor->value],
            'preparing' => [AppointmentStatus::Preparing->value],
            'waiting' => [AppointmentStatus::CheckedIn->value],
            'in_consultation' => [AppointmentStatus::InConsultation->value],
            default => [
                AppointmentStatus::ReadyForDoctor->value,
                AppointmentStatus::Preparing->value,
                AppointmentStatus::CheckedIn->value,
                AppointmentStatus::InConsultation->value,
            ],
        };
    }

    private function doctorIdForCurrentUser(): ?int
    {
        $user = auth()->user();

        if (! $user || in_array($user->role->value, ['super_admin', 'admin'], true)) {
            return null;
        }

        return $user->professional_id;
    }
}
