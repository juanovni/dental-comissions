<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialResponseTimeRoiChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 35;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Tiempo de respuesta vs ROI semanal';

    protected ?string $description = 'Velocidad promedio de respuesta comparada con revenue social generado.';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected ?string $maxHeight = '360px';

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->responseTimeVsRevenueData($this->getWidgetPeriodFilters());

        return $this->baseApexOptions([
            'chart' => [
                'height' => 360,
                'type' => 'line',
            ],
            'colors' => ['#f97316', '#10b981'],
            'legend' => [
                'fontSize' => '12px',
                'markers' => ['size' => 6],
                'position' => 'top',
                'show' => true,
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 6,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '48%',
                ],
            ],
            'series' => [
                [
                    'name' => 'Minutos respuesta',
                    'type' => 'bar',
                    'data' => $data['response_minutes'],
                ],
                [
                    'name' => 'Revenue',
                    'type' => 'line',
                    'data' => $data['revenue'],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [0, 4],
            ],
            'xaxis' => [
                'categories' => $data['labels'],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'yaxis' => [
                [
                    'decimalsInFloat' => 0,
                    'title' => ['text' => 'Minutos'],
                ],
                [
                    'opposite' => true,
                    'seriesName' => 'Revenue',
                    'title' => ['text' => 'Revenue'],
                ],
            ],
        ]);
    }
}
