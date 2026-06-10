<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Models\ActivityRecord;

class ApexTopProceduresChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $heading = 'Procedimientos mas realizados';

    protected ?string $description = 'Top 8 del mes actual para detectar demanda real.';

    protected ?string $maxHeight = '320px';

    protected function getOptions(): array
    {
        $rows = ActivityRecord::query()
            ->join('procedures', 'procedures.id', '=', 'activity_records.procedure_id')
            ->whereBetween('activity_records.activity_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('procedures.name as label, count(*) as total')
            ->groupBy('procedures.name')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'label');

        return $this->baseApexOptions([
            'chart' => [
                'height' => 320,
                'type' => 'bar',
            ],
            'colors' => ['#1d7afc'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'borderRadiusApplication' => 'end',
                    'horizontal' => true,
                    'barHeight' => '58%',
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
                'labels' => [
                    'style' => [
                        'colors' => '#374151',
                        'fontSize' => '12px',
                        'fontWeight' => 500,
                    ],
                ],
            ],
        ]);
    }
}
