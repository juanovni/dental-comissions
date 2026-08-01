<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentReminder;
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
        $now ??= Carbon::now($this->settings->clinicTimezone());

        $summary = [
            'whatsapp_first_sent' => 0,
            'whatsapp_second_sent' => 0,
            'whatsapp_skipped' => 0,
            'no_response_alerts_created' => 0,
        ];

        if ($this->settings->appointmentReminderWhatsappEnabled()) {
            $summary['whatsapp_first_sent'] = $this->sendWhatsappReminders('first', $this->settings->appointmentReminderFirstHoursBefore(), $now);
            $summary['whatsapp_second_sent'] = $this->sendWhatsappReminders('second', $this->settings->appointmentReminderSecondHoursBefore(), $now);
        } else {
            $summary['whatsapp_skipped'] = 1;
        }

        if ($this->settings->appointmentReminderInternalAlertOnNoResponse()) {
            $summary['no_response_alerts_created'] = $this->createNoResponseAlerts($now);
        }

        return $summary;
    }

    private function createNoResponseAlerts(Carbon $now): int
    {
        $created = 0;
        $cutoff = $now->copy()->subMinutes($this->settings->appointmentReminderNoResponseAlertMinutes());

        AppointmentReminder::query()
            ->with(['appointment.patient', 'appointment.doctor', 'appointment.procedure'])
            ->where('channel', 'whatsapp')
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $cutoff)
            ->where(function (Builder $query): void {
                $query->whereNull('metadata->no_response_alert_created_at')
                    ->orWhere('metadata->no_response_alert_created_at', '');
            })
            ->whereHas('appointment', fn (Builder $query): Builder => $query
                ->whereIn('status', [
                    AppointmentStatus::PendingConfirmation->value,
                    AppointmentStatus::Scheduled->value,
                    AppointmentStatus::Rescheduled->value,
                ]))
            ->each(function (AppointmentReminder $reminder) use ($now, &$created): void {
                $appointment = $reminder->appointment;

                if (! $appointment || $this->hasNoResponseAlert($appointment, $reminder)) {
                    $this->markNoResponseAlertCreated($reminder, $now);

                    return;
                }

                AppointmentNote::create([
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'visibility' => 'internal',
                    'note_type' => 'whatsapp_no_response',
                    'note' => $this->buildNoResponseAlertNote($appointment, $reminder),
                    'is_pinned' => true,
                ]);

                $this->markNoResponseAlertCreated($reminder, $now);
                $created++;
            });

        return $created;
    }

    private function sendWhatsappReminders(string $type, int $hoursBefore, Carbon $now): int
    {
        $sent = 0;

        foreach ($this->dueAppointments($hoursBefore, $now)->get() as $appointment) {
            if (($appointment->metadata['whatsapp_notifications_consent'] ?? null) === false) {
                continue;
            }

            if ($this->hasReminder($appointment, 'whatsapp', $type)) {
                continue;
            }

            if ($type === 'second' && ! $this->hasPreviousSentReminderBeforeWindow($appointment, 'whatsapp', 'first', $hoursBefore)) {
                continue;
            }

            $phone = $this->normalizePhone($appointment->patient?->phone);
            $message = $this->buildWhatsappMessage($appointment, $type);

            if (! $phone) {
                $this->recordReminder($appointment, 'whatsapp', $type, 'failed', null, $message, 'Paciente sin telefono.', $now);

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
                $now,
            );

            if ($wasSent) {
                $sent++;
            }
        }

        return $sent;
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

    private function hasPreviousSentReminderBeforeWindow(Appointment $appointment, string $channel, string $type, int $hoursBefore): bool
    {
        $windowStartsAt = $appointment->scheduled_at
            ?->copy()
            ->subHours($hoursBefore);

        if (! $windowStartsAt) {
            return false;
        }

        return AppointmentReminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('channel', $channel)
            ->where('reminder_type', $type)
            ->where('status', 'sent')
            ->where('sent_at', '<=', $windowStartsAt)
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
        ?Carbon $now = null,
    ): AppointmentReminder {
        $now ??= Carbon::now($this->settings->clinicTimezone());

        return AppointmentReminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'channel' => $channel,
            'reminder_type' => $type,
            'status' => $status,
            'to_phone' => $phone,
            'message' => $message,
            'scheduled_for' => $appointment->scheduled_at,
            'sent_at' => in_array($status, ['sent', 'queued'], true) ? $now : null,
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

    private function hasNoResponseAlert(Appointment $appointment, AppointmentReminder $reminder): bool
    {
        return AppointmentNote::query()
            ->where('appointment_id', $appointment->id)
            ->where('note_type', 'whatsapp_no_response')
            ->where('note', 'like', '%recordatorio '.$reminder->reminder_type.'%')
            ->exists();
    }

    private function buildNoResponseAlertNote(Appointment $appointment, AppointmentReminder $reminder): string
    {
        $patient = $appointment->patient?->full_name ?? 'Paciente';
        $time = $appointment->scheduled_at?->format('d/m/Y h:i a') ?? 'la hora programada';

        return "{$patient} no respondió el recordatorio {$reminder->reminder_type} de WhatsApp para la cita del {$time}. Revisar confirmación con recepción.";
    }

    private function markNoResponseAlertCreated(AppointmentReminder $reminder, Carbon $now): void
    {
        $metadata = $reminder->metadata ?? [];
        $metadata['no_response_alert_created_at'] = $now->toISOString();

        $reminder->update(['metadata' => $metadata]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        return $digits !== '' ? $digits : null;
    }
}
