<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\SocialComment;
use Carbon\Carbon;

class AppointmentCreationService
{
    public function createFromSocialLead(SocialComment $comment, array $data): Appointment
    {
        $comment->loadMissing(['socialIdentity.patient', 'convertedPatient', 'suggestedProcedure', 'socialPost']);

        $patientId = $data['patient_id']
            ?? $comment->converted_patient_id
            ?? $comment->socialIdentity?->patient_id;

        $procedureId = $data['procedure_id']
            ?? $comment->suggested_procedure_id;

        $doctorId = $data['doctor_id']
            ?? $data['assigned_doctor_id']
            ?? $comment->suggested_doctor_id;

        if ($doctorId && ! empty($data['scheduled_at'])) {
            $doctor = Professional::find($doctorId);
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $duration = (int) ($data['duration_minutes'] ?? app(SocialCrmSettingsService::class)->appointmentSlotDuration());
            $slotEnd = $scheduledAt->copy()->addMinutes($duration);

            if (! $doctor || ! app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor($doctor, $scheduledAt, $slotEnd)) {
                throw new \RuntimeException('El horario seleccionado ya no esta disponible para el doctor.');
            }
        }

        $previousStage = $comment->pipeline_stage;

        if ($patientId && ! empty($data['scheduled_at']) && $existingAppointment = $this->activeFutureAppointmentForPatient((int) $patientId)) {
            $existingAppointment->update([
                'social_comment_id' => $comment->id,
                'social_identity_id' => $comment->social_identity_id,
                'social_post_id' => $comment->social_post_id,
                'procedure_id' => $procedureId,
                'doctor_id' => $doctorId,
                'assigned_user_id' => $data['assigned_user_id'] ?? $existingAppointment->assigned_user_id,
                'source' => $data['source'] ?? $existingAppointment->source,
                'notes' => trim(($existingAppointment->notes ?? '') . "\nReprogramada desde WhatsApp/social. " . ($data['notes'] ?? '')),
                'created_by' => $data['created_by'] ?? $existingAppointment->created_by,
                'metadata' => array_merge($existingAppointment->metadata ?? [], $data['metadata'] ?? [], [
                    'rescheduled_from_social_lead' => true,
                    'previous_scheduled_at' => $existingAppointment->scheduled_at?->toDateTimeString(),
                ]),
            ]);

            $appointment = app(AppointmentWorkflowService::class)->reschedule(
                $existingAppointment->refresh(),
                Carbon::parse($data['scheduled_at']),
                $data['duration_minutes'] ?? null,
            );

            $this->markCommentAppointmentCreated($comment, $patientId);
            $this->recordAppointmentAction($comment, $appointment, $patientId, $procedureId, $doctorId, $previousStage, $data, true);

            return $appointment->refresh();
        }

        $appointment = Appointment::create([
            'patient_id' => $patientId,
            'social_comment_id' => $comment->id,
            'social_identity_id' => $comment->social_identity_id,
            'social_post_id' => $comment->social_post_id,
            'procedure_id' => $procedureId,
            'doctor_id' => $doctorId,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'status' => $data['status'] ?? AppointmentStatus::PendingConfirmation,
            'source' => $data['source'] ?? AppointmentSource::AdminManual,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->markCommentAppointmentCreated($comment, $patientId);
        $this->recordAppointmentAction($comment, $appointment, $patientId, $procedureId, $doctorId, $previousStage, $data, false);

        return $appointment->refresh();
    }

    private function activeFutureAppointmentForPatient(int $patientId): ?Appointment
    {
        return Appointment::query()
            ->where('patient_id', $patientId)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->whereIn('status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Rescheduled->value,
            ])
            ->latest('scheduled_at')
            ->latest('id')
            ->first();
    }

    private function markCommentAppointmentCreated(SocialComment $comment, mixed $patientId): void
    {
        $comment->update([
            'conversion_status' => SocialConversionStatus::AppointmentCreated,
            'pipeline_stage' => SocialPipelineStage::Appointment,
            'converted_patient_id' => $patientId ?: $comment->converted_patient_id,
            'converted_at' => $patientId ? ($comment->converted_at ?: now()) : $comment->converted_at,
            'is_hidden' => false,
        ]);
    }

    private function recordAppointmentAction(
        SocialComment $comment,
        Appointment $appointment,
        mixed $patientId,
        mixed $procedureId,
        mixed $doctorId,
        mixed $previousStage,
        array $data,
        bool $rescheduled,
    ): void {
        $comment->actions()->create([
            'action' => SocialCommentActionType::AppointmentCreated,
            'performed_by' => $data['created_by'] ?? auth()->id(),
            'notes' => $rescheduled
                ? ($data['audit_notes'] ?? 'Cita existente reprogramada desde lead social.')
                : ($data['audit_notes'] ?? 'Cita creada desde lead social.'),
            'external_response' => [
                'appointment_id' => $appointment->id,
                'patient_id' => $patientId,
                'procedure_id' => $procedureId,
                'doctor_id' => $doctorId,
                'scheduled_at' => $appointment->scheduled_at?->toISOString(),
                'status' => $appointment->status->value,
                'source' => $appointment->source->value,
                'rescheduled' => $rescheduled,
                'pipeline_from' => $previousStage?->value,
                'pipeline_to' => SocialPipelineStage::Appointment->value,
            ],
        ]);
    }
}
