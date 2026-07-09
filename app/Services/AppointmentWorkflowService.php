<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
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

            $comment = $appointment->socialComment;

            if ($comment && $reason && $this->isDefinitiveCancellation($reason)) {
                app(SocialPipelineTransitionService::class)->toLost(
                    $comment,
                    $reason,
                    'Cita cancelada definitivamente por el lead.',
                );
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

            $comment = $appointment->socialComment;

            if ($comment) {
                app(SocialPipelineTransitionService::class)->toProposal(
                    $comment,
                    null,
                    'Cita completada. Lead movido a presupuesto.',
                );
            }

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

            $comment = $appointment->socialComment;

            if ($comment) {
                app(SocialPipelineTransitionService::class)->moveToNoShow(
                    $comment,
                    'No asistio a la cita. Lead devuelto a calificado con seguimiento.',
                );
            }

            return $appointment->fresh();
        });
    }

    public function syncToCalendar(Appointment $appointment): ?string
    {
        return $this->calendarService->syncAppointment($appointment);
    }

    private function isDefinitiveCancellation(string $reason): bool
    {
        $definitive = [
            'no me interesa', 'ya no quiero', 'no quiero', 'caro', 'muy caro',
            'está muy caro', 'esta muy caro', 'mejor no', 'ya no', 'descartar',
            'fui a otro lado', 'otra clinica', 'ya me atendi', 'cancelar todo',
            'cancelar definitivamente', 'no voy', 'no voy a ir',
        ];

        $lower = mb_strtolower(trim($reason));

        foreach ($definitive as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }
}
