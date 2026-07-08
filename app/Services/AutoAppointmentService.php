<?php

namespace App\Services;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Models\Appointment;
use App\Models\SocialComment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoAppointmentService
{
    public function __construct(
        private AppointmentCreationService $creationService,
        private AppointmentWorkflowService $workflowService,
    ) {}

    public function createFromDetectedIntent(SocialComment $comment, array $agentResponse): ?Appointment
    {
        $candidate = $agentResponse['appointment_candidate'] ?? [];
        $wantsAppointment = $candidate['wants_appointment'] ?? false;
        $preferredDate = $candidate['preferred_date_parsed'] ?? null;
        $preferredTime = $candidate['preferred_time_parsed'] ?? null;
        $intentType = $candidate['intent_type'] ?? $agentResponse['intent'] ?? '';
        $intentConfidence = $candidate['intent_confidence'] ?? $agentResponse['closing_opportunity_score'] ?? 0;

        $isBookingIntent = in_array($intentType, ['appointment_interest', 'ready_to_book'], true)
            || $wantsAppointment
            || $agentResponse['intent'] === 'ready_to_book';

        if (!$isBookingIntent) {
            return null;
        }

        if (!$preferredDate && !$preferredTime) {
            return null;
        }

        $settings = app(SocialCrmSettingsService::class);
        $autoConfirm = $settings->appointmentAutoConfirm();
        $duration = $settings->appointmentSlotDuration();

        $scheduledAt = $this->buildScheduledAt($preferredDate, $preferredTime, $duration);

        if (!$scheduledAt) {
            Log::info('AutoAppointmentService: no se pudo construir fecha/hora', [
                'comment_id' => $comment->id,
                'preferred_date' => $preferredDate,
                'preferred_time' => $preferredTime,
            ]);
            return null;
        }

        if ($scheduledAt->isPast()) {
            Log::info('AutoAppointmentService: fecha ya pasada, no se crea cita', [
                'comment_id' => $comment->id,
                'scheduled_at' => $scheduledAt->toISOString(),
            ]);
            return null;
        }

        try {
            $data = [
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $duration,
                'doctor_id' => $comment->suggested_doctor_id,
                'procedure_id' => $comment->suggested_procedure_id,
                'status' => $autoConfirm ? AppointmentStatus::Confirmed : AppointmentStatus::PendingConfirmation,
                'source' => AppointmentSource::WhatsappAi,
                'notes' => 'Cita creada automaticamente desde WhatsApp. Intencion: ' . $intentType,
                'created_by' => null,
                'audit_notes' => 'Cita creada automaticamente por deteccion de intencion en WhatsApp.',
                'metadata' => [
                    'auto_created' => true,
                    'intent_type' => $intentType,
                    'intent_confidence' => $intentConfidence,
                    'appointment_candidate' => $candidate,
                ],
            ];

            $appointment = $this->creationService->createFromSocialLead($comment, $data);

            $this->workflowService->syncToCalendar($appointment);

            $comment->actions()->create([
                'action' => SocialCommentActionType::AppointmentAutoCreated,
                'performed_by' => null,
                'notes' => 'Cita creada automaticamente desde WhatsApp.',
                'external_response' => [
                    'appointment_id' => $appointment->id,
                    'scheduled_at' => $scheduledAt->toISOString(),
                    'doctor_id' => $comment->suggested_doctor_id,
                    'procedure_id' => $comment->suggested_procedure_id,
                    'auto_confirm' => $autoConfirm,
                ],
            ]);

            Log::info('AutoAppointmentService: cita creada exitosamente', [
                'appointment_id' => $appointment->id,
                'comment_id' => $comment->id,
                'scheduled_at' => $scheduledAt->toISOString(),
            ]);

            return $appointment;
        } catch (\Throwable $e) {
            Log::error('AutoAppointmentService: error creando cita', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            $comment->actions()->create([
                'action' => SocialCommentActionType::Error,
                'performed_by' => null,
                'notes' => 'Error al crear cita automatica: ' . $e->getMessage(),
                'external_response' => [
                    'error' => $e->getMessage(),
                    'preferred_date' => $preferredDate,
                    'preferred_time' => $preferredTime,
                ],
            ]);

            return null;
        }
    }

    private function buildScheduledAt(?string $date, ?string $time, int $duration): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            if ($time) {
                return Carbon::parse($date . ' ' . $time);
            }

            $defaultTime = app(SocialCrmSettingsService::class)->appointmentClinicOpen();
            return Carbon::parse($date . ' ' . $defaultTime);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
