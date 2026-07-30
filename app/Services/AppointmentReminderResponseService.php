<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AppointmentReminderResponseService
{
    public function __construct(
        private SocialCrmSettingsService $settings,
        private GoogleCalendarService $calendarService,
    ) {}

    public function handle(WhatsappMessage $message): ?array
    {
        $appointment = $this->findAppointmentForPhone($message->from_phone);

        if (! $appointment) {
            return null;
        }

        $action = app(BookingConfirmationService::class)->detectLocally(
            $message->message_body,
            in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Rescheduled], true),
            $appointment,
        );

        if ($action === 'not_booking_response' || $action === 'forgetful') {
            return null;
        }

        return match ($action) {
            'confirmed' => $this->confirm($appointment, $message),
            'modified' => $this->requestReview($appointment, $message, 'Solicitud de reprogramacion por WhatsApp.'),
            'rejected' => $this->requestReview($appointment, $message, 'Paciente indica que no podra asistir.'),
            default => null,
        };
    }

    private function confirm(Appointment $appointment, WhatsappMessage $message): array
    {
        if (! in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::ReadyForDoctor, AppointmentStatus::InConsultation, AppointmentStatus::Completed], true)) {
            app(AppointmentFlowService::class)->transition(
                $appointment,
                AppointmentStatus::Confirmed,
                'whatsapp_reminder',
                null,
                [
                    'whatsapp_message_id' => $message->id,
                    'message_body' => $message->message_body,
                ],
            );

            $this->calendarService->createOrUpdateEvent($appointment->fresh(['doctor', 'patient', 'procedure']));
        }

        $message->markAsConfirmed();

        $appointment = $appointment->fresh(['doctor', 'procedure']);

        return [
            'action' => 'confirmed',
            'appointment' => $appointment,
            'reply' => $this->confirmedReply($appointment),
        ];
    }

    private function requestReview(Appointment $appointment, WhatsappMessage $message, string $reason): array
    {
        $appointment->appointmentNotes()->create([
            'patient_id' => $appointment->patient_id,
            'created_by' => null,
            'visibility' => 'internal',
            'note_type' => 'whatsapp',
            'note' => trim($reason.' Mensaje: '.$message->message_body),
        ]);

        $message->markAsNeedsReview($reason);

        return [
            'action' => 'needs_review',
            'appointment' => $appointment,
            'reply' => 'Recibimos tu mensaje. Recepcion revisara tu cita y te ayudara con el cambio.',
        ];
    }

    private function findAppointmentForPhone(string $phone): ?Appointment
    {
        $phones = $this->phoneVariants($phone);

        if ($phones === []) {
            return null;
        }

        $appointmentFromReminder = AppointmentReminder::query()
            ->with(['appointment.patient', 'appointment.doctor', 'appointment.procedure'])
            ->where('channel', 'whatsapp')
            ->whereIn('status', ['sent', 'failed'])
            ->whereIn('to_phone', $phones)
            ->whereHas('appointment', fn (Builder $query): Builder => $this->pendingAppointmentQuery($query))
            ->latest('sent_at')
            ->latest('id')
            ->first()
            ?->appointment;

        if ($appointmentFromReminder) {
            return $appointmentFromReminder;
        }

        $patientIds = Patient::query()
            ->where(function (Builder $query) use ($phones): void {
                foreach ($phones as $phone) {
                    $query->orWhereRaw("regexp_replace(coalesce(phone, ''), '[^0-9]', '', 'g') = ?", [$phone]);
                }
            })
            ->pluck('id');

        if ($patientIds->count() !== 1) {
            return null;
        }

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure'])
            ->whereIn('patient_id', $patientIds)
            ->tap(fn (Builder $query): Builder => $this->pendingAppointmentQuery($query))
            ->orderBy('scheduled_at')
            ->first();
    }

    private function pendingAppointmentQuery(Builder $query): Builder
    {
        $now = Carbon::now($this->settings->clinicTimezone());

        return $query
            ->whereIn('status', [
                AppointmentStatus::PendingConfirmation->value,
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Rescheduled->value,
            ])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', $now->copy()->subHours(3))
            ->where('scheduled_at', '<=', $now->copy()->addDays(7));
    }

    private function confirmedReply(Appointment $appointment): string
    {
        $date = $appointment->scheduled_at?->isoFormat('dddd D [de] MMMM [a las] h:mm A') ?? 'el horario acordado';
        $doctor = $appointment->doctor?->name ? " con {$appointment->doctor->name}" : '';
        $procedure = $appointment->procedure?->name ? " para {$appointment->procedure->name}" : '';

        return "Perfecto, tu cita{$procedure}{$doctor} queda confirmada para {$date}. Te esperamos en la clinica.";
    }

    private function phoneVariants(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if ($digits === '') {
            return [];
        }

        $variants = [$digits];

        if (str_starts_with($digits, '593') && strlen($digits) > 3) {
            $variants[] = '0'.substr($digits, 3);
        }

        if (str_starts_with($digits, '0') && strlen($digits) > 1) {
            $variants[] = '593'.substr($digits, 1);
        }

        return array_values(array_unique($variants));
    }
}
