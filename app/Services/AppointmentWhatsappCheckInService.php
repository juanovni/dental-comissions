<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AppointmentWhatsappCheckInService
{
    public function __construct(
        private AppointmentFlowService $flowService,
        private SocialCrmSettingsService $settings,
    ) {}

    /**
     * @return array{reply: string}|null
     */
    public function handle(WhatsappMessage $message): ?array
    {
        $body = trim($message->message_body ?? '');
        $phone = $message->from_phone ?? '';

        if ($body === '' || $phone === '') {
            return null;
        }

        if ($pending = $this->pendingSelection($phone)) {
            return $this->handlePendingSelection($pending, $message);
        }

        if (! $this->isArrivalIntent($body)) {
            return null;
        }

        $appointments = $this->todayAppointmentsForPhone($phone);

        if ($appointments->isEmpty()) {
            return [
                'reply' => 'No encontramos una cita para hoy con este número. Acércate a recepción para ayudarte.',
            ];
        }

        if ($appointments->count() > 1) {
            $this->storePendingSelection($phone, $appointments);

            return [
                'reply' => $this->buildMultipleAppointmentsReply($appointments),
            ];
        }

        return [
            'reply' => $this->checkInAppointment($appointments->first(), 'whatsapp'),
        ];
    }

    /**
     * @param  array{appointment_ids: array<int, int>}  $pending
     * @return array{reply: string}
     */
    private function handlePendingSelection(array $pending, WhatsappMessage $message): array
    {
        $body = trim($message->message_body ?? '');
        $selectedIndex = ctype_digit($body) ? (int) $body : null;
        $appointmentIds = array_values($pending['appointment_ids'] ?? []);

        if (! $selectedIndex || ! isset($appointmentIds[$selectedIndex - 1])) {
            return [
                'reply' => 'Por favor responde con el número de la cita en la que ya llegaste.',
            ];
        }

        $appointment = Appointment::query()
            ->with(['doctor', 'procedure', 'patient'])
            ->find($appointmentIds[$selectedIndex - 1]);

        Cache::forget($this->cacheKey($message->from_phone));

        if (! $appointment || ! $this->isToday($appointment)) {
            return [
                'reply' => 'No pudimos confirmar esa cita. Acércate a recepción para ayudarte.',
            ];
        }

        return [
            'reply' => $this->checkInAppointment($appointment, 'whatsapp'),
        ];
    }

    private function checkInAppointment(Appointment $appointment, string $source): string
    {
        if ($appointment->status === AppointmentStatus::CheckedIn || $appointment->checked_in_at) {
            return 'Tu llegada ya fue confirmada anteriormente. Recepción ya está al tanto.';
        }

        if (! $this->canCheckIn($appointment)) {
            return 'Esta cita no está disponible para check-in por WhatsApp. Acércate a recepción para ayudarte.';
        }

        $this->flowService->transition(
            $appointment,
            AppointmentStatus::CheckedIn,
            $source,
            null,
            ['check_in_source' => $source],
        );

        return 'Listo, recepción fue notificada de tu llegada. Por favor espera a que te llamen.';
    }

    private function isArrivalIntent(string $body): bool
    {
        $normalized = Str::of($body)->lower()->ascii()->squish()->toString();

        return in_array($normalized, [
            'llegue',
            'ya llegue',
            'estoy aqui',
            'estoy en recepcion',
            'ya estoy aqui',
            'ya estoy en recepcion',
            'estoy en la clinica',
            'ya estoy en la clinica',
        ], true);
    }

    private function todayAppointmentsForPhone(string $phone): Collection
    {
        $phoneVariants = $this->phoneSearchVariants($phone);
        $now = Carbon::now($this->settings->clinicTimezone());

        if ($phoneVariants === []) {
            return collect();
        }

        return Appointment::query()
            ->with(['patient', 'doctor', 'procedure'])
            ->whereBetween('scheduled_at', [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ])
            ->whereHas('patient', function ($query) use ($phoneVariants): void {
                $query->where(function ($query) use ($phoneVariants): void {
                    foreach ($phoneVariants as $variant) {
                        $query->orWhereRaw("regexp_replace(coalesce(phone, ''), '[^0-9]', '', 'g') like ?", ['%'.$variant]);
                    }
                });
            })
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::Completed->value,
                AppointmentStatus::NoShow->value,
            ])
            ->orderBy('scheduled_at')
            ->get();
    }

    private function canCheckIn(Appointment $appointment): bool
    {
        return in_array($appointment->status, [
            AppointmentStatus::PendingConfirmation,
            AppointmentStatus::Scheduled,
            AppointmentStatus::Confirmed,
            AppointmentStatus::Rescheduled,
        ], true);
    }

    private function isToday(Appointment $appointment): bool
    {
        if (! $appointment->scheduled_at) {
            return false;
        }

        return $appointment->scheduled_at
            ->copy()
            ->timezone($this->settings->clinicTimezone())
            ->isSameDay(Carbon::now($this->settings->clinicTimezone()));
    }

    private function buildMultipleAppointmentsReply(Collection $appointments): string
    {
        $lines = ['Encontramos más de una cita para hoy:', ''];

        foreach ($appointments->values() as $index => $appointment) {
            $time = $appointment->scheduled_at?->format('h:i a') ?? 'hora pendiente';
            $doctor = $appointment->doctor?->name ?? 'Sin doctor';
            $procedure = $appointment->procedure?->name ?? 'Sin procedimiento';
            $lines[] = ($index + 1).". {$time} con {$doctor} - {$procedure}";
        }

        $lines[] = '';
        $lines[] = 'Responde con el número de la cita en la que ya llegaste.';

        return implode("\n", $lines);
    }

    private function storePendingSelection(string $phone, Collection $appointments): void
    {
        Cache::put($this->cacheKey($phone), [
            'appointment_ids' => $appointments->pluck('id')->values()->all(),
        ], now()->addMinutes(15));
    }

    /**
     * @return array{appointment_ids: array<int, int>}|null
     */
    private function pendingSelection(string $phone): ?array
    {
        $pending = Cache::get($this->cacheKey($phone));

        return is_array($pending) ? $pending : null;
    }

    private function cacheKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: $phone;

        return 'appointment-whatsapp-check-in:'.$digits;
    }

    /**
     * @return array<int, string>
     */
    private function phoneSearchVariants(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

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

        if (strlen($digits) >= 9) {
            $variants[] = substr($digits, -9);
        }

        return array_values(array_unique($variants));
    }
}
