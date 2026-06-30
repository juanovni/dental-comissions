<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class AppointmentAvailabilityService
{
    public function nextAvailableSlots(?int $count = null): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $count = $count ?? $settings->appointmentMaxSlotsOffer();
        $leadTimeHours = $settings->appointmentLeadTimeHours();
        $duration = $settings->appointmentSlotDuration();
        $open = $settings->appointmentClinicOpen();
        $close = $settings->appointmentClinicClose();
        $clinicDays = $settings->appointmentClinicDays();

        $openMinutes = $this->timeToMinutes($open);
        $closeMinutes = $this->timeToMinutes($close);

        $start = now()->addHours($leadTimeHours);

        $currentMinutes = $start->hour * 60 + $start->minute;
        $rounded = (int) (ceil($currentMinutes / $duration) * $duration);

        $cursor = $start->copy()->startOfDay()->addMinutes($rounded);

        $slots = [];
        $maxIterations = 200;

        while (count($slots) < $count && $maxIterations > 0) {
            $maxIterations--;

            $dayOfWeek = (int) $cursor->format('w');

            if (! in_array($dayOfWeek, $clinicDays, true)) {
                $cursor = $cursor->copy()->addDay()->startOfDay()->addMinutes($openMinutes);
                continue;
            }

            $cursorMinutes = $cursor->hour * 60 + $cursor->minute;

            if ($cursorMinutes < $openMinutes) {
                $cursor = $cursor->copy()->startOfDay()->addMinutes($openMinutes);
                continue;
            }

            $slotEndMinutes = $cursorMinutes + $duration;

            if ($slotEndMinutes > $closeMinutes) {
                $cursor = $cursor->copy()->addDay()->startOfDay()->addMinutes($openMinutes);
                continue;
            }

            $slotEnd = $cursor->copy()->addMinutes($duration);

            $existing = Appointment::where('scheduled_at', '>=', $cursor)
                ->where('scheduled_at', '<', $slotEnd)
                ->whereNotIn('status', [
                    AppointmentStatus::Cancelled->value,
                    AppointmentStatus::NoShow->value,
                ])
                ->exists();

            if (! $existing) {
                $slots[] = $cursor->copy();
            }

            $cursor->addMinutes($duration);
        }

        return $slots;
    }

    public function formatSlotsForPrompt(array $slots): string
    {
        if (empty($slots)) {
            return 'No hay horarios disponibles proximamente.';
        }

        $lines = [];

        foreach ($slots as $slot) {
            $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
            $dayOfWeek = (int) $slot->format('w');
            $dayName = $dayNames[$dayOfWeek];

            $prefix = match (true) {
                $slot->isToday() => 'Hoy',
                $slot->isTomorrow() => 'Manana',
                default => $dayName.' '.$slot->format('j'),
            };

            $lines[] = $prefix.' '.$slot->format('g:i A');
        }

        return implode("\n", $lines);
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }
}
