<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentFlowService
{
    /**
     * @return array<string, list<AppointmentStatus>>
     */
    public function allowedTransitions(): array
    {
        return [
            AppointmentStatus::PendingConfirmation->value => [
                AppointmentStatus::Confirmed,
                AppointmentStatus::CheckedIn,
                AppointmentStatus::Rescheduled,
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ],
            AppointmentStatus::Scheduled->value => [
                AppointmentStatus::Confirmed,
                AppointmentStatus::CheckedIn,
                AppointmentStatus::Completed,
                AppointmentStatus::Rescheduled,
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ],
            AppointmentStatus::Confirmed->value => [
                AppointmentStatus::CheckedIn,
                AppointmentStatus::Completed,
                AppointmentStatus::Rescheduled,
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ],
            AppointmentStatus::Rescheduled->value => [
                AppointmentStatus::Confirmed,
                AppointmentStatus::CheckedIn,
                AppointmentStatus::Completed,
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ],
            AppointmentStatus::CheckedIn->value => [
                AppointmentStatus::Preparing,
                AppointmentStatus::ReadyForDoctor,
                AppointmentStatus::Cancelled,
            ],
            AppointmentStatus::Preparing->value => [
                AppointmentStatus::CheckedIn,
                AppointmentStatus::ReadyForDoctor,
                AppointmentStatus::Cancelled,
            ],
            AppointmentStatus::ReadyForDoctor->value => [
                AppointmentStatus::Preparing,
                AppointmentStatus::InConsultation,
                AppointmentStatus::Cancelled,
            ],
            AppointmentStatus::InConsultation->value => [
                AppointmentStatus::Completed,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function transition(
        Appointment $appointment,
        AppointmentStatus $toStatus,
        string $source = 'system',
        ?int $createdBy = null,
        array $metadata = [],
    ): Appointment {
        $fromStatus = $appointment->status;

        if ($fromStatus === $toStatus) {
            return $appointment;
        }

        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'status' => "No se puede cambiar la cita de {$fromStatus->label()} a {$toStatus->label()}.",
            ]);
        }

        return DB::transaction(function () use ($appointment, $fromStatus, $toStatus, $source, $createdBy, $metadata): Appointment {
            $appointment->update($this->statusPayload($toStatus, $metadata));

            $appointment->events()->create([
                'event_type' => 'status_changed',
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'occurred_at' => now(),
                'created_by' => $createdBy,
                'source' => $source,
                'metadata' => $metadata ?: null,
            ]);

            return $appointment;
        });
    }

    public function canTransition(AppointmentStatus $fromStatus, AppointmentStatus $toStatus): bool
    {
        return in_array($toStatus, $this->allowedTransitions()[$fromStatus->value] ?? [], true);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function statusPayload(AppointmentStatus $status, array $metadata): array
    {
        $payload = ['status' => $status];

        return match ($status) {
            AppointmentStatus::Confirmed => $payload + ['confirmed_at' => now()],
            AppointmentStatus::CheckedIn => $payload + [
                'checked_in_at' => now(),
                'check_in_source' => $metadata['check_in_source'] ?? $metadata['source'] ?? null,
            ],
            AppointmentStatus::Preparing => $payload + ['preparation_started_at' => now()],
            AppointmentStatus::ReadyForDoctor => $payload + ['ready_for_doctor_at' => now()],
            AppointmentStatus::InConsultation => $payload + ['consultation_started_at' => now()],
            AppointmentStatus::Completed => $payload + [
                'consultation_finished_at' => now(),
                'completed_at' => now(),
            ],
            AppointmentStatus::Cancelled => $payload + ['cancelled_at' => now()],
            AppointmentStatus::NoShow => $payload + ['no_show_at' => now()],
            default => $payload,
        };
    }
}
