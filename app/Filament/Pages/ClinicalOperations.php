<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Professional;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClinicalOperations extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?string $navigationLabel = 'Operacion clinica';

    protected static ?string $title = 'Operacion clinica';

    protected static ?string $slug = 'clinical-operations';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.clinical-operations';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRolePermission('patient_flow_admin.view') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Gestion y analitica operativa del flujo diario de pacientes.';
    }

    public function metrics(): array
    {
        $query = $this->todayAppointments();
        $attended = (clone $query)->where('status', AppointmentStatus::Completed->value)->count();
        $checkedIn = (clone $query)->whereNotNull('checked_in_at')->get();
        $consulted = (clone $query)->whereNotNull('consultation_started_at')->get();
        $scheduled = (clone $query)->count();
        $onTime = $checkedIn->filter(fn (Appointment $appointment): bool => $appointment->scheduled_at && $appointment->checked_in_at?->lte($appointment->scheduled_at->copy()->addMinutes(5)))->count();

        return [
            'scheduled' => $scheduled,
            'confirmed' => (clone $query)->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::CheckedIn->value, AppointmentStatus::Preparing->value, AppointmentStatus::ReadyForDoctor->value, AppointmentStatus::InConsultation->value, AppointmentStatus::Completed->value])->count(),
            'attended' => $attended,
            'cancelled' => (clone $query)->where('status', AppointmentStatus::Cancelled->value)->count(),
            'no_show' => (clone $query)->where('status', AppointmentStatus::NoShow->value)->count(),
            'avg_wait' => (int) round($checkedIn->map(fn (Appointment $appointment): ?int => $appointment->waitingMinutes())->filter(fn (?int $minutes): bool => $minutes !== null)->avg() ?? 0),
            'avg_consultation' => (int) round($consulted->map(fn (Appointment $appointment): ?int => $appointment->consultationMinutes())->filter(fn (?int $minutes): bool => $minutes !== null)->avg() ?? 0),
            'punctuality' => $checkedIn->count() > 0 ? (int) round(($onTime / $checkedIn->count()) * 100) : 0,
        ];
    }

    public function statusDistribution(): array
    {
        return [
            AppointmentStatus::CheckedIn->value => ['label' => 'En espera', 'count' => $this->countByStatus(AppointmentStatus::CheckedIn), 'color' => '#d97706'],
            AppointmentStatus::Preparing->value => ['label' => 'En preparacion', 'count' => $this->countByStatus(AppointmentStatus::Preparing), 'color' => '#7c3aed'],
            AppointmentStatus::ReadyForDoctor->value => ['label' => 'Listo para doctor', 'count' => $this->countByStatus(AppointmentStatus::ReadyForDoctor), 'color' => '#16a34a'],
            AppointmentStatus::InConsultation->value => ['label' => 'En consulta', 'count' => $this->countByStatus(AppointmentStatus::InConsultation), 'color' => '#0f766e'],
            AppointmentStatus::Completed->value => ['label' => 'Finalizada', 'count' => $this->countByStatus(AppointmentStatus::Completed), 'color' => '#6b7280'],
        ];
    }

    public function alerts(): Collection
    {
        $alerts = collect();
        $metrics = $this->metrics();

        if ($metrics['avg_wait'] > 20) {
            $alerts->push('Tiempo de espera promedio mayor a 20 min');
        }

        $this->todayAppointments()
            ->with('doctor')
            ->whereIn('status', [AppointmentStatus::CheckedIn->value, AppointmentStatus::Preparing->value, AppointmentStatus::ReadyForDoctor->value])
            ->get()
            ->groupBy('doctor_id')
            ->each(function (Collection $appointments) use ($alerts): void {
                if ($appointments->count() >= 3) {
                    $doctor = $appointments->first()->doctor?->name ?? 'Doctor sin asignar';
                    $alerts->push($doctor.' tiene '.$appointments->count().' pacientes acumulados');
                }
            });

        $possibleNoShows = $this->todayAppointments()
            ->whereIn('status', [AppointmentStatus::PendingConfirmation->value, AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value, AppointmentStatus::Rescheduled->value])
            ->whereNull('checked_in_at')
            ->where('scheduled_at', '<', now()->subMinutes(15))
            ->count();

        if ($possibleNoShows > 0) {
            $alerts->push($possibleNoShows.' posible No Show sin confirmar');
        }

        return $alerts->values();
    }

    public function doctorLoad(): Collection
    {
        return Professional::query()
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Professional $doctor): array {
                $appointments = $this->todayAppointments()->where('doctor_id', $doctor->id);
                $total = (clone $appointments)->count();
                $attended = (clone $appointments)->where('status', AppointmentStatus::Completed->value)->count();
                $waiting = (clone $appointments)->whereIn('status', [AppointmentStatus::CheckedIn->value, AppointmentStatus::Preparing->value, AppointmentStatus::ReadyForDoctor->value])->count();
                $inConsultation = (clone $appointments)->where('status', AppointmentStatus::InConsultation->value)->count();
                $saturation = $total > 0 ? min(100, (int) round((($waiting + $inConsultation) / max($total, 1)) * 100)) : 0;

                return [
                    'name' => $doctor->name,
                    'total' => $total,
                    'attended' => $attended,
                    'waiting' => $waiting,
                    'saturation' => $saturation,
                ];
            });
    }

    private function countByStatus(AppointmentStatus $status): int
    {
        return $this->todayAppointments()->where('status', $status->value)->count();
    }

    private function todayAppointments(): Builder
    {
        return Appointment::query()->whereDate('scheduled_at', today());
    }
}
