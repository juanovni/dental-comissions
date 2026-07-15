<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialConversionFunnelChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 36;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $maxHeight = '240px';

    protected string $cardClass = 'social-roi-panel social-roi-chart-panel';

    protected ?string $heading = 'Embudo social';

    protected ?string $description = 'Comentario -> WhatsApp -> Ficha -> Cita -> Actividad.';

    public function getDescription(): ?string
    {
        return $this->description;
    }

    protected function getOptions(): array
    {
        $funnel = app(SocialRoiService::class)->funnelData($this->getWidgetPeriodFilters());

        return $this->baseApexOptions([
            'chart' => [
                'height' => 240,
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
