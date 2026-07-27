<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\VoiceCall;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AppointmentReminderService
{
    public function __construct(
        private SocialCrmSettingsService $settings,
        private WhatsappService $whatsappService,
    ) {}

    /**
     * @return array<string, int>
     */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();

        $summary = [
            'whatsapp_first_sent' => 0,
            'whatsapp_second_sent' => 0,
            'whatsapp_skipped' => 0,
            'voice_queued' => 0,
            'voice_skipped' => 0,
        ];

        if ($this->settings->appointmentReminderWhatsappEnabled()) {
            $summary['whatsapp_first_sent'] = $this->sendWhatsappReminders('first', $this->settings->appointmentReminderFirstHoursBefore(), $now);
            $summary['whatsapp_second_sent'] = $this->sendWhatsappReminders('second', $this->settings->appointmentReminderSecondHoursBefore(), $now);
        } else {
            $summary['whatsapp_skipped'] = 1;
        }

        if ($this->settings->appointmentReminderPityVoiceEnabled()) {
            $summary['voice_queued'] = $this->queueVoiceEscalations($this->settings->appointmentReminderVoiceEscalationHoursBefore(), $now);
        } else {
            $summary['voice_skipped'] = 1;
        }

        return $summary;
    }

    private function sendWhatsappReminders(string $type, int $hoursBefore, Carbon $now): int
    {
        $sent = 0;

        foreach ($this->dueAppointments($hoursBefore, $now)->get() as $appointment) {
            if ($this->hasReminder($appointment, 'whatsapp', $type)) {
                continue;
            }

            $phone = $this->normalizePhone($appointment->patient?->phone);
            $message = $this->buildWhatsappMessage($appointment, $type);

            if (! $phone) {
                $this->recordReminder($appointment, 'whatsapp', $type, 'failed', null, $message, 'Paciente sin telefono.');

                continue;
            }

            $wasSent = $this->whatsappService->sendMessage($phone, $message);

            $this->recordReminder(
                $appointment,
                'whatsapp',
                $type,
                $wasSent ? 'sent' : 'failed',
                $phone,
                $message,
                $wasSent ? null : 'WhatsApp no envio el mensaje. Revisa credenciales o respuesta de Meta.',
            );

            if ($wasSent) {
                $sent++;
            }
        }

        return $sent;
    }

    private function queueVoiceEscalations(int $hoursBefore, Carbon $now): int
    {
        $queued = 0;

        foreach ($this->dueAppointments($hoursBefore, $now, onlyUnconfirmed: true)->get() as $appointment) {
            if ($this->hasReminder($appointment, 'pity_voice', 'escalation')) {
                continue;
            }

            $phone = $this->normalizePhone($appointment->patient?->phone);

            if (! $phone) {
                $this->recordReminder($appointment, 'pity_voice', 'escalation', 'failed', null, null, 'Paciente sin telefono.');

                continue;
            }

            VoiceCall::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'channel' => VoiceChannelType::Telnyx,
                'provider' => 'pity_voice',
                'from_phone' => (string) config('services.telnyx.from_number', ''),
                'to_phone' => $phone,
                'status' => VoiceCallStatus::Started,
                'started_at' => now(),
                'metadata' => [
                    'source' => 'appointment_reminder',
                    'note' => 'Escalamiento registrado. La llamada saliente real se integrara con el proveedor de voz.',
                ],
            ]);

            $this->recordReminder($appointment, 'pity_voice', 'escalation', 'queued', $phone);
            $queued++;
        }

        return $queued;
    }

    private function dueAppointments(int $hoursBefore, Carbon $now, ?bool $onlyUnconfirmed = null): Builder
    {
        $onlyUnconfirmed ??= $this->settings->appointmentRemindersOnlyUnconfirmed();
        $statuses = $onlyUnconfirmed
            ? [AppointmentStatus::PendingConfirmation, AppointmentStatus::Scheduled]
            : [AppointmentStatus::PendingConfirmation, AppointmentStatus::Scheduled, AppointmentStatus::Confirmed, AppointmentStatus::Rescheduled];

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure'])
            ->whereIn('status', collect($statuses)->map->value->all())
            ->whereBetween('scheduled_at', [$now, $now->copy()->addHours($hoursBefore)])
            ->orderBy('scheduled_at');
    }

    private function hasReminder(Appointment $appointment, string $channel, string $type): bool
    {
        return AppointmentReminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('channel', $channel)
            ->where('reminder_type', $type)
            ->exists();
    }

    private function recordReminder(
        Appointment $appointment,
        string $channel,
        string $type,
        string $status,
        ?string $phone,
        ?string $message = null,
        ?string $error = null,
    ): AppointmentReminder {
        return AppointmentReminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'channel' => $channel,
            'reminder_type' => $type,
            'status' => $status,
            'to_phone' => $phone,
            'message' => $message,
            'scheduled_for' => $appointment->scheduled_at,
            'sent_at' => in_array($status, ['sent', 'queued'], true) ? now() : null,
            'last_error' => $error,
            'metadata' => [
                'appointment_status' => $appointment->status->value,
            ],
        ]);
    }

    private function buildWhatsappMessage(Appointment $appointment, string $type): string
    {
        $name = $appointment->patient?->full_name ?? 'paciente';
        $time = $appointment->scheduled_at?->format('d/m/Y h:i a') ?? 'la hora acordada';
        $doctor = $appointment->doctor?->name ? ' con '.$appointment->doctor->name : '';
        $procedure = $appointment->procedure?->name ? ' para '.$appointment->procedure->name : '';

        $prefix = $type === 'second' ? 'Te recordamos nuevamente' : 'Te recordamos';

        return "Hola {$name}, {$prefix} tu cita{$procedure}{$doctor} el {$time}. Responde CONFIRMO para confirmar o REPROGRAMAR si necesitas cambiarla.";
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        return $digits !== '' ? $digits : null;
    }
}
