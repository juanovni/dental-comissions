<?php

namespace App\Filament\Widgets;

use App\Models\ActivityRecord;
use Filament\Widgets\ChartWidget;

class TopDoctorsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Doctores con mas procedimientos';

    protected ?string $description = 'Cantidad de actividades del mes actual.';

    protected function getData(): array
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

        return [
            'datasets' => [
                [
                    'label' => 'Procedimientos',
                    'data' => $rows->values()->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => '#0369a1',
                    'borderColor' => '#bae6fd',
                    'borderRadius' => 9,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(3, 105, 161, 0.10)'],
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
