<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class SocialRoiPeriod
{
    public static function presets(): array
    {
        return [
            'today' => 'Hoy',
            'last_7_days' => 'Ultimos 7 dias',
            'last_30_days' => 'Ultimos 30 dias',
            'current_month' => 'Mes actual',
            'previous_month' => 'Mes anterior',
            'last_12_weeks' => 'Ultimas 12 semanas',
            'custom' => 'Personalizado',
        ];
    }

    public static function datesForPreset(?string $preset): array
    {
        $today = now();

        return match ($preset) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'last_7_days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'current_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'previous_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_12_weeks' => [$today->copy()->startOfWeek()->subWeeks(11)->startOfDay(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    public static function resolve(?array $filters = null): array
    {
        $preset = $filters['period'] ?? 'last_30_days';

        if ($preset === 'custom') {
            $from = filled($filters['from'] ?? null)
                ? Carbon::parse($filters['from'])->startOfDay()
                : now()->subDays(29)->startOfDay();
            $until = filled($filters['until'] ?? null)
                ? Carbon::parse($filters['until'])->endOfDay()
                : now()->endOfDay();
        } else {
            [$from, $until] = self::datesForPreset($preset);
        }

        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = (int) $from->diffInDays($until) + 1;
        $previousUntil = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousUntil->copy()->subDays($days - 1)->startOfDay();

        return [
            'from' => $from,
            'until' => $until,
            'from_date' => $from->toDateString(),
            'until_date' => $until->toDateString(),
            'previous_from' => $previousFrom,
            'previous_until' => $previousUntil,
            'previous_from_date' => $previousFrom->toDateString(),
            'previous_until_date' => $previousUntil->toDateString(),
            'label' => $from->translatedFormat('d M Y') . ' - ' . $until->translatedFormat('d M Y'),
            'previous_label' => $previousFrom->translatedFormat('d M Y') . ' - ' . $previousUntil->translatedFormat('d M Y'),
            'comparison_label' => $preset === 'custom'
                ? $previousFrom->translatedFormat('d M Y') . ' - ' . $previousUntil->translatedFormat('d M Y')
                : (self::presets()[$preset] ?? self::presets()['last_30_days']),
        ];
    }
}
