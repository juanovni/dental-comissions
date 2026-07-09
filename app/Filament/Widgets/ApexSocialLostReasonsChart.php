<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Services\SocialRoiService;

class ApexSocialLostReasonsChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 37;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $heading = 'Fuga por motivo de perdida';

    protected ?string $description = 'Valor estimado perdido y frecuencia por objecion comercial.';

    protected ?string $maxHeight = '270px';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->lostReasonsData($this->getWidgetPeriodFilters());

        return $this->baseApexOptions([
            'chart' => [
                'height' => 270,
                'type' => 'line',
            ],
            'colors' => ['#dc2626', '#f97316'],
            'legend' => [
                'fontSize' => '12px',
                'markers' => ['size' => 6],
                'position' => 'top',
                'show' => true,
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 8,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '46%',
                ],
            ],
            'series' => [
                [
                    'name' => 'Valor perdido USD',
                    'type' => 'bar',
                    'data' => $data['values'],
                ],
                [
                    'name' => 'Leads',
                    'type' => 'line',
                    'data' => $data['counts'],
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
                    'seriesName' => 'Valor perdido USD',
                    'title' => ['text' => 'USD'],
                ],
                [
                    'decimalsInFloat' => 0,
                    'opposite' => true,
                    'seriesName' => 'Leads',
                    'title' => ['text' => 'Leads'],
                ],
            ],
        ]);
    }
}
