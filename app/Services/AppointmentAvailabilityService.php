<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\Professional;
use Carbon\Carbon;

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
        $googleService = app(GoogleCalendarService::class);

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

            $existing = $this->hasAppointmentConflict(null, $cursor, $slotEnd)
                || $this->hasActiveHoldConflict(null, $cursor, $slotEnd);
            $clinicAvailable = $googleService->isClinicSlotAvailable($cursor, $slotEnd);

            if (! $existing && $clinicAvailable) {
                $slots[] = $cursor->copy();
            }

            $cursor->addMinutes($duration);
        }

        return $slots;
    }

    public function nextAvailableSlotsForDoctor(
        Professional $professional,
        ?int $count = null,
        ?Carbon $dayStart = null,
        ?Carbon $dayEnd = null,
    ): array {
        $settings = app(SocialCrmSettingsService::class);
        $count = $count ?? $settings->appointmentMaxSlotsOffer();
        $duration = $settings->appointmentSlotDuration();
        $open = $settings->appointmentClinicOpen();
        $close = $settings->appointmentClinicClose();
        $clinicDays = $settings->appointmentClinicDays();

        $googleService = app(GoogleCalendarService::class);

        $slots = [];
        $date = $dayStart?->copy() ?? now()->addHours($settings->appointmentLeadTimeHours());
        $maxIterations = 200;

        while (count($slots) < $count && $maxIterations > 0) {
            $maxIterations--;

            $dayOfWeek = (int) $date->format('w');

            if (! in_array($dayOfWeek, $clinicDays, true)) {
                $date = $date->copy()->addDay()->startOfDay();
                continue;
            }

            $currentOpen = ($dayStart && $date->isSameDay($dayStart)) || (! $dayStart && $date->isToday())
                ? max($this->timeToMinutes($open), $date->hour * 60 + $date->minute)
                : $this->timeToMinutes($open);

            $currentClose = $dayEnd && $date->isSameDay($dayEnd)
                ? min($this->timeToMinutes($close), $dayEnd->hour * 60 + $dayEnd->minute)
                : $this->timeToMinutes($close);

            $cursor = $date->copy()->startOfDay()->addMinutes(
                (int) (ceil($currentOpen / $duration) * $duration)
            );

            $daySlots = [];

            while (count($slots) + count($daySlots) < $count) {
                $cursorMinutes = $cursor->hour * 60 + $cursor->minute;

                if ($cursorMinutes < $currentOpen) {
                    $cursor = $cursor->copy()->startOfDay()->addMinutes($currentOpen);
                    continue;
                }

                $slotEndMinutes = $cursorMinutes + $duration;

                if ($slotEndMinutes > $currentClose) {
                    break;
                }

                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($duration);

                $existing = $this->hasAppointmentConflict($professional->id, $slotStart, $slotEnd)
                    || $this->hasActiveHoldConflict($professional->id, $slotStart, $slotEnd);

                $googleAvailable = $existing
                    ? false
                    : $googleService->isClinicSlotAvailable($slotStart, $slotEnd, $professional);

                if (! $existing && $googleAvailable) {
                    $daySlots[] = $slotStart;
                }

                $cursor->addMinutes($duration);
            }

            $slots = array_merge($slots, $daySlots);
            $date = $date->copy()->addDay()->startOfDay();
        }

        return array_slice($slots, 0, $count);
    }

    public function isSlotAvailableForDoctor(Professional $doctor, Carbon $start, Carbon $end): bool
    {
        $settings = app(SocialCrmSettingsService::class);
        $clinicDays = $settings->appointmentClinicDays();

        if ($start->lessThan(now()->addHours($settings->appointmentLeadTimeHours())->startOfMinute())) {
            return false;
        }

        if (! in_array((int) $start->format('w'), $clinicDays, true)) {
            return false;
        }

        $openMinutes = $this->timeToMinutes($settings->appointmentClinicOpen());
        $closeMinutes = $this->timeToMinutes($settings->appointmentClinicClose());
        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;

        if (! $start->isSameDay($end) || $startMinutes < $openMinutes || $endMinutes > $closeMinutes) {
            return false;
        }

        if ($this->hasAppointmentConflict($doctor->id, $start, $end)
            || $this->hasActiveHoldConflict($doctor->id, $start, $end)) {
            return false;
        }

        return app(GoogleCalendarService::class)->isClinicSlotAvailable($start, $end, $doctor);
    }

    public function availabilityWindow(?Carbon $preferredDate = null, int $maxDaysWithSlots = 5, int $maxDaysToScan = 21): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $duration = $settings->appointmentSlotDuration();
        $openMinutes = $this->timeToMinutes($settings->appointmentClinicOpen());
        $closeMinutes = $this->timeToMinutes($settings->appointmentClinicClose());
        $clinicDays = $settings->appointmentClinicDays();
        $leadTimeHours = $settings->appointmentLeadTimeHours();
        $googleService = app(GoogleCalendarService::class);

        $startFrom = now()->addHours($leadTimeHours)->startOfMinute();
        $cursor = $preferredDate?->copy()->startOfDay()->max($startFrom) ?? $startFrom;

        $days = [];
        $preferredDateFull = false;
        $firstAvailableDay = null;
        $daysWithSlots = 0;
        $scanned = 0;

        while ($daysWithSlots < $maxDaysWithSlots && $scanned < $maxDaysToScan) {
            $scanned++;
            $date = $cursor->copy()->startOfDay();
            $dayOfWeek = (int) $date->format('w');

            if (! in_array($dayOfWeek, $clinicDays, true)) {
                $cursor->addDay();
                continue;
            }

            $startMinute = $date->isToday()
                ? max($openMinutes, (int) (ceil(($startFrom->hour * 60 + $startFrom->minute) / $duration) * $duration))
                : $openMinutes;

            $daySlots = [];

            for ($minutes = $startMinute; $minutes + $duration <= $closeMinutes; $minutes += $duration) {
                $slotStart = $date->copy()->addMinutes($minutes);
                $slotEnd = $slotStart->copy()->addMinutes($duration);

                $available = ! $this->hasAppointmentConflict(null, $slotStart, $slotEnd)
                    && ! $this->hasActiveHoldConflict(null, $slotStart, $slotEnd)
                    && $googleService->isClinicSlotAvailable($slotStart, $slotEnd);

                if ($available) {
                    $daySlots[] = $slotStart;
                }
            }

            $isFull = empty($daySlots);

            if (! $isFull) {
                $daysWithSlots++;
            }

            if ($isFull && $preferredDate && $date->isSameDay($preferredDate)) {
                $preferredDateFull = true;
            }

            if (! $isFull && ! $firstAvailableDay) {
                $firstAvailableDay = $date;
            }

            $days[] = [
                'date' => $date,
                'label' => $date->isoFormat('ddd D MMM'),
                'long_label' => $date->isoFormat('dddd D [de] MMMM [del] YYYY'),
                'slots' => $daySlots,
                'slot_count' => count($daySlots),
                'is_full' => $isFull,
                'is_today' => $date->isToday(),
                'is_preferred' => $preferredDate && $date->isSameDay($preferredDate),
            ];

            $cursor->addDay();
        }

        return [
            'days' => $days,
            'preferred_date_full' => $preferredDateFull,
            'first_available_day' => $firstAvailableDay,
        ];
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

    private function hasAppointmentConflict(?int $doctorId, Carbon $start, Carbon $end): bool
    {
        $defaultDuration = app(SocialCrmSettingsService::class)->appointmentSlotDuration();

        $query = Appointment::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $end)
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        return $query->get(['scheduled_at', 'duration_minutes'])
            ->contains(function (Appointment $appointment) use ($start, $end, $defaultDuration): bool {
                $appointmentStart = $appointment->scheduled_at;
                $appointmentEnd = $appointmentStart->copy()->addMinutes(
                    $appointment->duration_minutes ?: $defaultDuration,
                );

                return $start->lessThan($appointmentEnd) && $end->greaterThan($appointmentStart);
            });
    }

    public function hasActiveHoldConflict(?int $doctorId, Carbon $start, Carbon $end): bool
    {
        $query = AppointmentSlotHold::query()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);

        if ($doctorId) {
            $query->where(function ($query) use ($doctorId): void {
                $query->whereNull('doctor_id')->orWhere('doctor_id', $doctorId);
            });
        }

        return $query->exists();
    }
}
