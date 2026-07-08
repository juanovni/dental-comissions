<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentWorkflowService
{
    public function __construct(
        private GoogleCalendarService $calendarService,
    ) {}

    public function confirm(Appointment $appointment, ?array $metadata = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $metadata): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->calendarService->createOrUpdateEvent($appointment);

            return $appointment->fresh();
        });
    }

    public function reschedule(
        Appointment $appointment,
        Carbon $newDate,
        int $durationMinutes = null,
        ?array $metadata = null,
    ): Appointment {
        return DB::transaction(function () use ($appointment, $newDate, $durationMinutes, $metadata): Appointment {
            $durationMinutes ??= $appointment->duration_minutes;

            $oldDate = $appointment->scheduled_at;

            $appointment->update([
                'scheduled_at' => $newDate,
                'duration_minutes' => $durationMinutes,
                'status' => AppointmentStatus::Rescheduled,
            ]);

            $this->calendarService->createOrUpdateEvent($appointment);

            return $appointment->fresh();
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null, ?array $metadata = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $metadata): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'cancelled_at' => now(),
                'notes' => $reason
                    ? trim(($appointment->notes ?? '') . "\nMotivo de cancelación: " . $reason)
                    : $appointment->notes,
            ]);

            if ($appointment->external_appointment_id) {
                $this->calendarService->deleteEvent($appointment);
            }

            return $appointment->fresh();
        });
    }

    public function complete(Appointment $appointment, ?array $metadata = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $metadata): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::Completed,
                'completed_at' => now(),
            ]);

            return $appointment->fresh();
        });
    }

    public function markNoShow(Appointment $appointment, ?array $metadata = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $metadata): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::NoShow,
                'no_show_at' => now(),
            ]);

            if ($appointment->external_appointment_id) {
                $this->calendarService->deleteEvent($appointment);
            }

            return $appointment->fresh();
        });
    }

    public function syncToCalendar(Appointment $appointment): ?string
    {
        return $this->calendarService->syncAppointment($appointment);
    }
}
