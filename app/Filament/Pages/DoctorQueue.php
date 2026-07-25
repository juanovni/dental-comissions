<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentFlowService;
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

    public string $noteText = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_doctor.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->professional?->name
            ? 'Cola de '.auth()->user()->professional->name
            : 'Pacientes listos, en preparacion y en consulta.';
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

    public function summary(): array
    {
        return [
            'waiting' => $this->baseQuery()->where('status', AppointmentStatus::CheckedIn->value)->count(),
            'preparing' => $this->baseQuery()->where('status', AppointmentStatus::Preparing->value)->count(),
            'ready' => $this->baseQuery()->where('status', AppointmentStatus::ReadyForDoctor->value)->count(),
            'in_consultation' => $this->baseQuery()->where('status', AppointmentStatus::InConsultation->value)->count(),
        ];
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
        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure', 'latestAppointmentNote'])
            ->whereDate('scheduled_at', today())
            ->when($this->doctorIdForCurrentUser(), fn (Builder $query, int $doctorId): Builder => $query->where('doctor_id', $doctorId));
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
