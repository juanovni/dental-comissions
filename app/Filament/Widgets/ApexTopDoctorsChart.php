<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Models\ActivityRecord;

class ApexTopDoctorsChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Doctores con mas procedimientos';

    protected ?string $description = 'Cantidad de actividades del mes actual.';

    protected function getOptions(): array
    {
        $rows = ActivityRecord::query()
            ->join('professionals', 'professionals.id', '=', 'activity_records.doctor_id')
            ->whereBetween('activity_records.activity_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('professionals.name as label, count(*) as total')
            ->groupBy('professionals.name')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'label');

        return $this->baseApexOptions([
            'chart' => [
                'height' => 320,
                'type' => 'bar',
            ],
            'colors' => ['#0369a1'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '58%',
                ],
            ],
            'series' => [
                [
                    'name' => 'Procedimientos',
                    'data' => $rows->values()->map(fn ($value) => (int) $value)->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $rows->keys()->all(),
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
                'labels' => [
                    'style' => [
                        'colors' => '#6b7280',
                        'fontSize' => '12px',
                    ],
                ],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => [
                    'style' => [
                        'colors' => '#6b7280',
                        'fontSize' => '12px',
                    ],
                ],
            ],
        ]);
    }
}
