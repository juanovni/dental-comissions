<?php

namespace App\Services;

use App\Enums\ProfessionalRole;
use App\Models\Professional;
use Carbon\Carbon;

class AppointmentSlotSearchService
{
    public function search(array $request): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $max = (int) ($request['max_options'] ?? $settings->appointmentMaxSlotsOffer());
        $date = $this->date($request['date'] ?? null);
        $period = $request['period'] ?? null;
        $time = $request['time'] ?? null;
        $doctor = $this->doctor($request['doctor_id'] ?? null);

        if (! $date) {
            return $this->genericNextSlots($doctor, $max, 'next_available');
        }

        $slots = [];

        if ($time) {
            $slots = array_merge($slots, $this->exactTime($date, $time, $doctor, 'exact_requested_time'));
        }

        if ($period) {
            $slots = array_merge($slots, $this->periodSlots($date, $period, $doctor, $max, 'same_day_same_period'));
        }

        $slots = array_merge($slots, $this->sameDayOtherPeriods($date, $period, $doctor, $max));
        $slots = array_merge($slots, $this->nearbyDays($date, $period, $doctor, $max));
        $slots = array_merge($slots, $this->nextDays($date, $period, $doctor, $max));
        $slots = array_merge($slots, $this->periodSlots($date->copy()->addWeek(), $period ?: 'afternoon', $doctor, $max, 'same_day_next_week'));

        if (! $doctor && $settings->appointmentAllowAlternativeDoctor()) {
            $slots = array_merge($slots, $this->otherDoctorSlots($date, $period ?: 'afternoon', $max));
        }

        return array_slice($this->unique($slots), 0, $max);
    }

    private function genericNextSlots(?Professional $doctor, int $max, string $strategy): array
    {
        $service = app(AppointmentAvailabilityService::class);
        $slots = $doctor
            ? $service->nextAvailableSlotsForDoctor($doctor, $max)
            : $service->nextAvailableSlots($max);

        return $this->map($slots, $doctor, $strategy);
    }

    private function exactTime(Carbon $date, string $time, ?Professional $doctor, string $strategy): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $start = Carbon::parse($date->format('Y-m-d').' '.$time);
        $end = $start->copy()->addMinutes($settings->appointmentSlotDuration());

        if ($doctor && app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor($doctor, $start, $end)) {
            return [$this->option($start, $doctor, $strategy, true)];
        }

        return [];
    }

    private function periodSlots(Carbon $date, string $period, ?Professional $doctor, int $max, string $strategy): array
    {
        [$from, $until] = $this->periodRange($date, $period);

        if (! $from || ! $until || $from->greaterThanOrEqualTo($until)) {
            return [];
        }

        $service = app(AppointmentAvailabilityService::class);
        $resolvedDoctor = $doctor ?: $this->fallbackDoctor();

        if (! $resolvedDoctor) {
            return [];
        }

        $slots = $service->nextAvailableSlotsForDoctor($resolvedDoctor, $max, $from, $until);

        return $this->map($slots, $resolvedDoctor, $strategy);
    }

    private function sameDayOtherPeriods(Carbon $date, ?string $period, ?Professional $doctor, int $max): array
    {
        $slots = [];
        $settings = app(SocialCrmSettingsService::class);
        foreach (['morning', 'afternoon', 'night'] as $candidate) {
            if ($candidate === $period) {
                continue;
            }

            if ($candidate === 'night' && ! $settings->appointmentNightEnabled()) {
                continue;
            }

            if ($candidate === 'morning' && ! $settings->appointmentMorningEnabled()) {
                continue;
            }

            if ($candidate === 'afternoon' && ! $settings->appointmentAfternoonEnabled()) {
                continue;
            }

            $slots = array_merge($slots, $this->periodSlots($date, $candidate, $doctor, $max, 'same_day_other_period'));
        }

        return $slots;
    }

    private function nearbyDays(Carbon $date, ?string $period, ?Professional $doctor, int $max): array
    {
        return array_merge(
            $this->periodSlots($date->copy()->subDay(), $period ?: 'afternoon', $doctor, $max, 'nearby_previous_day'),
            $this->periodSlots($date->copy()->addDay(), $period ?: 'afternoon', $doctor, $max, 'nearby_next_day'),
        );
    }

    private function nextDays(Carbon $date, ?string $period, ?Professional $doctor, int $max): array
    {
        $slots = [];
        $days = app(SocialCrmSettingsService::class)->appointmentSearchDays();

        for ($i = 2; $i <= $days + 1; $i++) {
            $slots = array_merge($slots, $this->periodSlots($date->copy()->addDays($i), $period ?: 'afternoon', $doctor, $max, 'next_available_days'));
        }

        return $slots;
    }

    private function otherDoctorSlots(Carbon $date, string $period, int $max): array
    {
        $slots = [];

        Professional::query()
            ->where('role', ProfessionalRole::Doctor->value)
            ->where('is_active', true)
            ->limit(5)
            ->get()
            ->each(function (Professional $doctor) use (&$slots, $date, $period, $max): void {
                $slots = array_merge($slots, $this->periodSlots($date, $period, $doctor, $max, 'alternative_doctor'));
            });

        return $slots;
    }

    private function periodRange(Carbon $date, string $period): array
    {
        $settings = app(SocialCrmSettingsService::class);
        $range = match ($period) {
            'morning' => [$settings->appointmentMorningStart(), $settings->appointmentMorningEnd()],
            'night' => [$settings->appointmentNightStart(), $settings->appointmentNightEnd()],
            default => [$settings->appointmentAfternoonStart(), $settings->appointmentAfternoonEnd()],
        };

        return [
            Carbon::parse($date->format('Y-m-d').' '.$range[0]),
            Carbon::parse($date->format('Y-m-d').' '.$range[1]),
        ];
    }

    private function map(array $slots, ?Professional $doctor, string $strategy): array
    {
        return array_map(fn (Carbon $slot): array => $this->option($slot, $doctor, $strategy), $slots);
    }

    private function option(Carbon $slot, ?Professional $doctor, string $strategy, bool $exact = false): array
    {
        return [
            'datetime' => $slot->format('Y-m-d H:i:s'),
            'doctor_id' => $doctor?->id,
            'strategy' => $strategy,
            'label' => $slot->isoFormat('dddd D [de] MMMM [a las] h:mm A'),
            'is_exact_match' => $exact,
        ];
    }

    private function unique(array $slots): array
    {
        $seen = [];

        return array_values(array_filter($slots, function (array $slot) use (&$seen): bool {
            $key = ($slot['doctor_id'] ?? 'clinic').'|'.$slot['datetime'];

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            return true;
        }));
    }

    private function date(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function doctor(mixed $doctorId): ?Professional
    {
        return $doctorId ? Professional::find($doctorId) : null;
    }

    private function fallbackDoctor(): ?Professional
    {
        return Professional::query()
            ->where('role', ProfessionalRole::Doctor->value)
            ->where('is_active', true)
            ->first();
    }
}
