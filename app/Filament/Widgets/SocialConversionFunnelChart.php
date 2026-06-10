<?php

namespace App\Filament\Widgets;

use App\Services\SocialRoiService;
use Filament\Widgets\ChartWidget;

class SocialConversionFunnelChart extends ChartWidget
{
    protected static ?int $sort = 31;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Embudo social';

    protected ?string $description = 'Comentario -> WhatsApp -> ficha -> actividad.';

    protected function getData(): array
    {
        $funnel = app(SocialRoiService::class)->funnelData();

        return [
            'datasets' => [
                [
                    'label' => 'Conversiones',
                    'data' => $funnel['values'],
                    'backgroundColor' => ['#0f766e', '#14b8a6', '#f59e0b', '#16a34a'],
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $funnel['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
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
