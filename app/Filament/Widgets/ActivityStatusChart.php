<?php

namespace App\Filament\Widgets;

use App\Enums\ActivityStatus;
use App\Models\ActivityRecord;
use Filament\Widgets\ChartWidget;

class ActivityStatusChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Actividades por estado';

    protected ?string $description = 'Control administrativo del mes actual.';

    protected function getData(): array
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

        return [
            'datasets' => [
                [
                    'label' => 'Actividades',
                    'data' => $data,
                    'backgroundColor' => ['#d97706', '#be123c', '#0f766e', '#0369a1', '#64748b'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 4,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '64%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
