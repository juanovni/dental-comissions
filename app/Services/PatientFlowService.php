<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatientFlowService
{
    public function markOnTheWay(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment->markOnTheWay();
            $this->recordEvent($appointment, 'patient_on_the_way');

            return $appointment->fresh();
        });
    }

    public function checkIn(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment->checkIn();
            $this->recordEvent($appointment, 'patient_checked_in');

            return $appointment->fresh();
        });
    }

    public function startConsultation(Appointment $appointment, ?string $room = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $room): Appointment {
            $appointment->startConsultation($room);
            $this->recordEvent($appointment, 'consultation_started', [
                'room' => $room ?? $appointment->room,
                'waiting_time_minutes' => $appointment->waiting_time_minutes,
            ]);

            return $appointment->fresh();
        });
    }

    public function finishConsultation(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $duration = $appointment->consultationDuration();
            $appointment->finishConsultation();
            $this->recordEvent($appointment, 'consultation_finished', [
                'consultation_duration_minutes' => $duration,
            ]);

            return $appointment->fresh();
        });
    }

    public function markNoShow(Appointment $appointment, ?string $reason = null, ?float $estimatedCost = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $estimatedCost): Appointment {
            $appointment->markNoShow();
            $this->recordEvent($appointment, 'appointment_no_show', [
                'reason' => $reason,
                'estimated_cost_lost' => $estimatedCost,
            ]);

            return $appointment->fresh();
        });
    }

    public function confirm(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment->confirm();
            $this->recordEvent($appointment, 'appointment_confirmed');

            return $appointment->fresh();
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason): Appointment {
            $appointment->cancel($reason);
            $this->recordEvent($appointment, 'appointment_cancelled', [
                'reason' => $reason,
            ]);

            return $appointment->fresh();
        });
    }

    public function reschedule(Appointment $appointment, Carbon $newDate, ?int $durationMinutes = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $newDate, $durationMinutes): Appointment {
            $oldDate = $appointment->scheduled_at;
            $appointment->update([
                'scheduled_at' => $newDate,
                'duration_minutes' => $durationMinutes ?? $appointment->duration_minutes,
                'status' => AppointmentStatus::Rescheduled,
            ]);
            $this->recordEvent($appointment, 'appointment_rescheduled', [
                'previous_scheduled_at' => $oldDate?->toDateTimeString(),
                'new_scheduled_at' => $newDate->toDateTimeString(),
            ]);

            return $appointment->fresh();
        });
    }

    public function getReceptionDashboardData(Carbon $date): array
    {
        $appointments = Appointment::query()
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
                'patient' => $a->patient?->full_name ?? '--',
                'scheduled_at' => $a->scheduled_at,
                'doctor' => $a->doctor?->name ?? '--',
                'procedure' => $a->procedure?->name ?? '--',
                'status' => $a->status,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'waiting_minutes' => $a->waitingTime(),
                'room' => $a->room,
                'is_late' => $a->isLate(),
                'late_minutes' => $a->lateMinutes(),
            ]);

        return $appointments->toArray();
    }

    public function getDoctorDashboardData(int $doctorId): array
    {
        $appointments = Appointment::query()
            ->with(['patient', 'procedure'])
            ->where('doctor_id', $doctorId)
            ->whereDate('scheduled_at', today())
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
                'patient' => $a->patient?->full_name ?? '--',
                'procedure' => $a->procedure?->name ?? '--',
                'scheduled_at' => $a->scheduled_at,
                'status' => $a->status,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
                'waiting_minutes' => $a->waitingTime(),
                'room' => $a->room,
            ]);

        return $appointments->toArray();
    }

    public function getKpis(Carbon $date): array
    {
        $dayAppointments = Appointment::query()->whereDate('scheduled_at', $date);
        $total = (clone $dayAppointments)->count();

        $completed = (clone $dayAppointments)->where('status', AppointmentStatus::Completed)->count();
        $noShows = (clone $dayAppointments)->where('status', AppointmentStatus::NoShow)->count();
        $cancelled = (clone $dayAppointments)->where('status', AppointmentStatus::Cancelled)->count();

        $attended = $completed;

        $avgWaitingTime = (clone $dayAppointments)
            ->whereNotNull('waiting_time_minutes')
            ->where('status', AppointmentStatus::Completed)
            ->avg('waiting_time_minutes');

        $avgConsultationTime = (clone $dayAppointments)
            ->whereNotNull('consultation_started_at')
            ->whereNotNull('consultation_finished_at')
            ->where('status', AppointmentStatus::Completed)
            ->get()
            ->avg(fn (Appointment $a): ?int => $a->consultationDuration());

        $doctorWaitingStats = (clone $dayAppointments)
            ->whereNotNull('doctor_id')
            ->whereNotNull('waiting_time_minutes')
            ->where('status', AppointmentStatus::Completed)
            ->selectRaw('doctor_id, AVG(waiting_time_minutes) as avg_wait')
            ->groupBy('doctor_id')
            ->orderBy('avg_wait')
            ->with('doctor')
            ->first();

        $doctorDelayStats = (clone $dayAppointments)
            ->whereNotNull('doctor_id')
            ->whereNotNull('waiting_time_minutes')
            ->where('status', AppointmentStatus::Completed)
            ->selectRaw('doctor_id, AVG(waiting_time_minutes) as avg_wait')
            ->groupBy('doctor_id')
            ->orderByDesc('avg_wait')
            ->with('doctor')
            ->first();

        $noShowRate = $total > 0 ? round(($noShows / $total) * 100, 1) : 0;

        return [
            'total_citas' => $total,
            'asistieron' => $attended,
            'no_show' => $noShows,
            'canceladas' => $cancelled,
            'tasa_no_show' => $noShowRate,
            'tiempo_promedio_espera' => $avgWaitingTime ? round((float) $avgWaitingTime) : null,
            'tiempo_promedio_consulta' => $avgConsultationTime ? round((float) $avgConsultationTime) : null,
            'doctor_menor_espera' => $doctorWaitingStats?->doctor?->name ?? null,
            'doctor_mayor_retraso' => $doctorDelayStats?->doctor?->name ?? null,
        ];
    }

    private function recordEvent(Appointment $appointment, string $event, ?array $metadata = null): AppointmentEvent
    {
        return AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'event' => $event,
            'performed_by' => auth()->id(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
