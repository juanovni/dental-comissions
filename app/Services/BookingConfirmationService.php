<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SocialCommentActionType;
use App\Models\Appointment;
use App\Models\SocialComment;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingConfirmationService
{
    public function __construct(
        private AppointmentWorkflowService $workflowService,
        private AppointmentIntentService $intentService,
        private WhatsappSalesAgentService $agentService,
    ) {}

    public const STALE_DAYS_THRESHOLD = 3;

    public function handleMessage(
        SocialComment $comment,
        WhatsappMessage $message,
        Appointment $appointment,
    ): array {
        $body = $message->message_body ?? '';

        $isConfirmed = in_array($appointment->status, [
            AppointmentStatus::Confirmed,
            AppointmentStatus::Rescheduled,
        ], true);

        $localResult = $this->detectLocally($body, $isConfirmed, $appointment);

        if ($localResult !== 'not_booking_response') {
            if ($localResult === 'confirmed' && $this->isAppointmentPast($appointment)) {
                $reply = $this->buildPastDateReply($appointment);

                $comment->actions()->create([
                    'action' => \App\Enums\SocialCommentActionType::BookingModified,
                    'notes' => 'Lead confirmo cita vencida. Se solicita nueva fecha.',
                    'external_response' => [
                        'appointment_id' => $appointment->id,
                        'scheduled_at' => $appointment->scheduled_at?->toDateTimeString(),
                    ],
                ]);

                return [
                    'action' => 'past_date',
                    'reply' => $reply,
                    'appointment' => $appointment,
                ];
            }

            if ($localResult === 'forgetful') {
                $reply = $this->buildReply('forgetful', $appointment);

                return [
                    'action' => 'forgetful',
                    'reply' => $reply,
                    'appointment' => $appointment,
                ];
            }

            $result = $this->executeAction($comment, $appointment, $localResult, $body);
            $reply = $this->buildReply($localResult, $appointment, $result['appointment']);

            return [
                'action' => $localResult,
                'reply' => $reply,
                'appointment' => $result['appointment'] ?? $appointment,
            ];
        }

        $aiResponse = $this->agentService->respond($comment, $message);

        $bookingResponse = $aiResponse['booking_response'] ?? null;

        if ($bookingResponse && in_array($bookingResponse, ['confirmed', 'rejected', 'modified', 'propose_alternatives'], true)) {
            $action = $bookingResponse === 'propose_alternatives' ? 'modified' : $bookingResponse;

            if ($action === 'confirmed' && $this->isAppointmentPast($appointment)) {
                $reply = $this->buildPastDateReply($appointment);

                return [
                    'action' => 'past_date',
                    'reply' => $reply,
                    'appointment' => $appointment,
                ];
            }

            $result = $this->executeAction($comment, $appointment, $action, $body, $aiResponse);
            $reply = $this->buildReply($action, $appointment, $result['appointment'] ?? $appointment);

            return [
                'action' => $action,
                'reply' => $reply,
                'appointment' => $result['appointment'] ?? $appointment,
            ];
        }

        return [
            'action' => 'no_decision',
            'reply' => $aiResponse['reply'] ?? 'Gracias por tu mensaje. ¿Te gustaria confirmar, modificar o cancelar tu cita?',
            'appointment' => $appointment,
        ];
    }

    public function isAppointmentPast(Appointment $appointment): bool
    {
        return $appointment->scheduled_at && $appointment->scheduled_at->isPast();
    }

    public function isAppointmentStale(Appointment $appointment): bool
    {
        if (!$appointment->created_at) {
            return false;
        }

        return $appointment->created_at->diffInDays(now()) > self::STALE_DAYS_THRESHOLD;
    }

    public function detectLocally(string $body, bool $isConfirmed = false, ?Appointment $appointment = null): string
    {
        $lower = mb_strtolower(trim($body));

        $lower = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $lower);

        $forgetful = [
            'no recuerdo', 'no me acuerdo', 'de que me hablas', 'de que cita',
            'que cita', 'cual cita', 'no se de que', 'no se a que te refieres',
            'disculpa no', 'no se que', 'que es eso', 'no tengo cita',
            'yo no agende', 'yo no pedi', 'nunca pedi', 'no se de que me hablas',
            'no recuerdo haber', 'no recuerdo ninguna',
        ];

        foreach ($forgetful as $phrase) {
            if (str_contains($lower, $phrase)) {
                return 'forgetful';
            }
        }

        $confirming = [
            'si', 'sí', 'ok', 'okay', 'dale', 'confirmo', 'confirmar',
            'adelante', 'esta bien', 'está bien', 'de acuerdo', 'perfecto',
            'excelente', 'claro', 'si gracias', 'sí gracias', 'confirmado',
            'listo', 'hecho', 'agendalo', 'agéndalo', 'agendar', 'reserva',
            'reservar', 'confirm', 'yes', 'simon', 'simón', 'sep', 'sipo',
            'confirmo la cita', 'confirmar cita', 'si quiero', 'sí quiero',
        ];

        $rejecting = [
            'no', 'no gracias', 'no quiero', 'cancelalo', 'cancelarlo',
            'cancelar', 'no me interesa', 'descartar', 'quit', 'cancel',
            'no gracias', 'no, gracias', 'no,gracias', 'ninguno',
            'ninguna', 'mejor no', 'ahora no', 'despues', 'después',
            'luego', 'mas adelante', 'más adelante', 'no, gracias',
            'no gracias,', 'no quiero la cita', 'cancelar cita',
        ];

        if ($isConfirmed) {
            $cancelConfirmed = [
                'quiero cancelar', 'necesito cancelar', 'puedo cancelar',
                'no voy a poder', 'no puedo ir', 'no podre ir', 'no podre asistir',
                'cancelar mi cita', 'cancelar la cita', 'no voy a asistir',
                'no puedo asistir', 'tengo que cancelar', 'me urge cancelar',
                'ya no voy', 'ya no puedo', 'cancelar por favor',
            ];
            foreach ($cancelConfirmed as $phrase) {
                if (str_contains($lower, $phrase)) {
                    return 'rejected';
                }
            }

            $rescheduleConfirmed = [
                'quiero cambiar', 'necesito cambiar', 'puedo cambiar',
                'cambiar la fecha', 'cambiar el dia', 'cambiar la hora',
                'reprogramar mi cita', 'reprogramar la cita',
                'mover la cita', 'correr la cita', 'posponer', 'aplazar',
            ];
            foreach ($rescheduleConfirmed as $phrase) {
                if (str_contains($lower, $phrase)) {
                    return 'modified';
                }
            }
        }

        if (in_array($lower, ['👍', '👌', '✅', '✔', '🙌', '🔥'], true)) {
            return 'confirmed';
        }

        if ($this->detectTimeChangeIntent($lower)) {
            return 'modified';
        }

        foreach ($confirming as $keyword) {
            if ($lower === $keyword || str_starts_with($lower, $keyword.' ') || str_starts_with($lower, $keyword.',')) {
                return 'confirmed';
            }
        }

        foreach ($rejecting as $keyword) {
            if ($lower === $keyword || str_starts_with($lower, $keyword.' ') || str_starts_with($lower, $keyword.',')) {
                return 'rejected';
            }
        }

        return 'not_booking_response';
    }

    private function detectTimeChangeIntent(string $text): bool
    {
        if (preg_match('/\b(mejor\s+(a\s+las|el|para|en\s+la)|otro\s+(dia|horario|día|fecha|hora)|cambiemos|cambiar\s+(la\s+)?(fecha|hora|dia|día)|reprogramar|reasignar)\b/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(mañana\s+mejor|tarde\s+mejor|noche\s+mejor|en\s+la\s+(mañana|tarde|noche))\b/iu', $text)) {
            return true;
        }

        if (preg_match('/\b(mas\s+tarde|más\s+tarde|otra\s+hora|otro\s+dia|otro\s+día|mejor\s+dia|mejor\s+día)\b/iu', $text)) {
            return true;
        }

        if (preg_match('/\d{1,2}:\d{2}/', $text)) {
            return true;
        }

        if (preg_match('/\b(a\s+las|para\s+las|desde\s+las)\s+\d{1,2}\b/', $text)) {
            return true;
        }

        if (preg_match('/\b(lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/iu', $text)) {
            return true;
        }

        return false;
    }

    public function executeAction(
        SocialComment $comment,
        Appointment $appointment,
        string $action,
        ?string $body = null,
        ?array $aiResponse = null,
    ): array {
        return match ($action) {
            'confirmed' => $this->confirmBooking($comment, $appointment, $body, $aiResponse),
            'rejected' => $this->rejectBooking($comment, $appointment, $body, $aiResponse),
            'modified' => $this->modifyBooking($comment, $appointment, $body, $aiResponse),
            default => ['appointment' => $appointment],
        };
    }

    private function confirmBooking(
        SocialComment $comment,
        Appointment $appointment,
        ?string $body,
        ?array $aiResponse,
    ): array {
        DB::transaction(function () use ($appointment, $comment, $body, $aiResponse) {
            $this->workflowService->confirm($appointment);
        });

        $comment->actions()->create([
            'action' => SocialCommentActionType::BookingConfirmed,
            'notes' => 'Lead confirmo la cita por WhatsApp.',
            'external_response' => [
                'appointment_id' => $appointment->id,
                'message_body' => $body,
            ],
        ]);

        return ['appointment' => $appointment->fresh()];
    }

    private function rejectBooking(
        SocialComment $comment,
        Appointment $appointment,
        ?string $body,
        ?array $aiResponse,
    ): array {
        $reason = $body ? "Rechazada por lead: {$body}" : 'Rechazada por lead sin motivo';

        DB::transaction(function () use ($appointment, $reason, $comment) {
            $this->workflowService->cancel($appointment, $reason);

            $comment->update([
                'conversion_status' => \App\Enums\SocialConversionStatus::WhatsappStarted,
                'appointment_scheduled_at' => null,
            ]);
        });

        $comment->actions()->create([
            'action' => SocialCommentActionType::BookingRejected,
            'notes' => 'Lead rechazo la cita.',
            'external_response' => [
                'appointment_id' => $appointment->id,
                'message_body' => $body,
            ],
        ]);

        return ['appointment' => $appointment->fresh()];
    }

    private function modifyBooking(
        SocialComment $comment,
        Appointment $appointment,
        ?string $body,
        ?array $aiResponse,
    ): array {
        $parsedDate = null;
        $parsedTime = null;

        if ($aiResponse && isset($aiResponse['appointment_candidate'])) {
            $candidate = $aiResponse['appointment_candidate'];
            $parsedDate = $candidate['preferred_date_parsed'] ?? null;
            $parsedTime = $candidate['preferred_time_parsed'] ?? null;
        }

        if (!$parsedDate && $body) {
            $intentResult = $this->intentService->extractFromText($body);
            $parsedDate = $intentResult['date'] ?? null;
            $parsedTime = $intentResult['time'] ?? null;
        }

        if ($parsedDate) {
            $time = $parsedTime ? Carbon::parse($parsedTime)->format('H:i') : $appointment->scheduled_at->format('H:i');
            $newDate = Carbon::parse($parsedDate)->setTimeFromTimeString($time);

            if (!$newDate->isPast()) {
                DB::transaction(function () use ($appointment, $newDate, $comment) {
                    $this->workflowService->reschedule($appointment, $newDate);
                });

                $comment->actions()->create([
                    'action' => SocialCommentActionType::BookingModified,
                    'notes' => "Lead solicito modificar la cita a {$newDate->format('d/m/Y H:i')}.",
                    'external_response' => [
                        'appointment_id' => $appointment->id,
                        'new_scheduled_at' => $newDate->toDateTimeString(),
                        'message_body' => $body,
                    ],
                ]);

                return ['appointment' => $appointment->fresh()];
            }
        }

        if ($parsedTime && $appointment->scheduled_at) {
            $existingDate = $appointment->scheduled_at->copy();
            $newDate = $existingDate->setTimeFromTimeString(Carbon::parse($parsedTime)->format('H:i'));

            if (!$newDate->isPast()) {
                DB::transaction(function () use ($appointment, $newDate, $comment) {
                    $this->workflowService->reschedule($appointment, $newDate);
                });

                $comment->actions()->create([
                    'action' => SocialCommentActionType::BookingModified,
                    'notes' => "Lead solicito modificar la cita a {$newDate->format('d/m/Y H:i')}.",
                    'external_response' => [
                        'appointment_id' => $appointment->id,
                        'new_scheduled_at' => $newDate->toDateTimeString(),
                        'message_body' => $body,
                    ],
                ]);

                return ['appointment' => $appointment->fresh()];
            }
        }

        $comment->actions()->create([
            'action' => SocialCommentActionType::BookingModified,
            'notes' => 'Lead solicito modificar la cita pero sin fecha valida.',
            'external_response' => [
                'appointment_id' => $appointment->id,
                'message_body' => $body,
                'parsed_date' => $parsedDate,
                'parsed_time' => $parsedTime,
            ],
        ]);

        return ['appointment' => $appointment];
    }

    public function buildPastDateReply(Appointment $appointment): string
    {
        $dateStr = $appointment->scheduled_at?->isoFormat('dddd D [de] MMMM') ?? 'la fecha';
        $doctorName = $appointment->doctor?->name ?? 'el doctor';

        return "Parece que la cita que teniamos agendada para el {$dateStr} ya paso.\n\n"
            ."¿Te gustaria agendar una nueva cita con {$doctorName}? "
            ."Por favor indicanos que dia y horario te queda mejor.";
    }

    public function buildReply(string $action, Appointment $appointment, ?Appointment $updatedAppointment = null): string
    {
        $appt = $updatedAppointment ?? $appointment;
        $doctorName = $appt->doctor?->name ?? 'el doctor';
        $dateStr = $appt->scheduled_at?->isoFormat('dddd D [de] MMMM [a las] h:mm A') ?? 'pendiente';

        return match ($action) {
            'confirmed' => "¡Perfecto! Tu cita ha sido confirmada.\n\n"
                ."📅 {$dateStr}\n"
                ."👨‍⚕️ {$doctorName}\n\n"
                ."Te esperamos en la clinica. Si necesitas reprogramar o cancelar, puedes escribirnos en cualquier momento.",
            'rejected' => "Entendido, hemos cancelado tu cita.\n\n"
                ."Si en el futuro deseas agendar una nueva cita, estaremos encantados de ayudarte. "
                ."Solo escribenos y te daremos los horarios disponibles.",
            'modified' => "Hemos recibido tu solicitud de cambio de horario.\n\n"
                .($appt->scheduled_at
                    ? "Tu cita ha sido reprogramada para:\n📅 {$appt->scheduled_at->isoFormat('dddd D [de] MMMM [a las] h:mm A')}\n👨‍⚕️ {$doctorName}"
                    : 'Por favor indicanos que dia y horario prefieres para tu nueva cita.')
                ."\n\nSi deseas otro horario, solo dimelo.",
            'forgetful' => "No te preocupes. Teniamos una cita agendada {$dateStr} con {$doctorName}.\n\n"
                ."¿Te gustaria confirmarla, cambiarla para otro dia o cancelarla?",
            'past_date' => $this->buildPastDateReply($appointment),
            default => 'Gracias por tu mensaje.',
        };
    }
}
