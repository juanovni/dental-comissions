<?php

namespace App\Filament\Widgets;

use App\Models\ActivityRecord;
use Filament\Widgets\ChartWidget;

class TopProceduresChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Procedimientos mas realizados';

    protected ?string $description = 'Top 8 del mes actual para detectar demanda real.';

    protected function getData(): array
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

        return [
            'datasets' => [
                [
                    'label' => 'Procedimientos',
                    'data' => $rows->values()->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => '#0f766e',
                    'borderColor' => '#99f6e4',
                    'borderRadius' => 10,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(15, 118, 110, 0.10)'],
                    'ticks' => ['precision' => 0],
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
