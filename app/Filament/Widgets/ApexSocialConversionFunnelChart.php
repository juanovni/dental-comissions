<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialConversionFunnelChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 31;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Embudo social';

    protected ?string $description = 'Comentario -> WhatsApp -> ficha -> actividad.';

    protected function getOptions(): array
    {
        $funnel = app(SocialRoiService::class)->funnelData();

        return $this->baseApexOptions([
            'chart' => [
                'height' => 320,
                'type' => 'bar',
            ],
            'colors' => ['#0f766e'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 8,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '56%',
                    'distributed' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Conversiones',
                    'data' => collect($funnel['values'])->map(fn ($value) => (int) $value)->all(),
                ],
            ],
            'xaxis' => [
                'categories' => $funnel['labels'],
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
            ],
        ]);
    }
}
