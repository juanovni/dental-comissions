<?php

namespace App\Filament\Widgets;

use App\Enums\ActivityStatus;
use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Models\ActivityRecord;

class ApexActivityStatusChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Actividades por estado';

    protected ?string $description = 'Control administrativo del mes actual.';

    protected function getOptions(): array
    {
        $counts = ActivityRecord::query()
            ->whereBetween('activity_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];

        foreach (ActivityStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
        }

        return $this->baseApexOptions([
            'chart' => [
                'height' => 300,
                'type' => 'donut',
            ],
            'colors' => ['#d97706', '#be123c', '#0f766e', '#0369a1', '#64748b'],
            'labels' => $labels,
            'legend' => [
                'fontSize' => '12px',
                'markers' => ['size' => 6],
                'position' => 'bottom',
                'show' => true,
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '64%',
                    ],
                ],
            ],
            'series' => $data,
            'stroke' => [
                'colors' => ['#ffffff'],
                'width' => 4,
            ],
        ]);
    }
}
