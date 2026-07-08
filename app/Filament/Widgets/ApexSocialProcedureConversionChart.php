<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiPeriod;
use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialProcedureConversionChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiPeriod;

    protected static ?int $sort = 34;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Eficiencia de conversion por procedimiento';

    protected ?string $description = 'Volumen de comentarios clasificados por IA vs tasa de conversion a actividad pagada.';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected ?string $maxHeight = '380px';

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->procedureConversionData(5, $this->pageFilters);

        return $this->baseApexOptions([
            'chart' => [
                'height' => 380,
                'type' => 'line',
            ],
            'colors' => ['#6366f1', '#10b981'],
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
                    'columnWidth' => '46%',
                ],
            ],
            'series' => [
                [
                    'name' => 'Comentarios IA',
                    'type' => 'bar',
                    'data' => $data['comments'],
                ],
                [
                    'name' => 'Conversion %',
                    'type' => 'line',
                    'data' => $data['conversion_rates'],
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
                    'title' => ['text' => 'Comentarios'],
                ],
                [
                    'max' => 100,
                    'opposite' => true,
                    'seriesName' => 'Conversion %',
                    'title' => ['text' => 'Conversion'],
                ],
            ],
        ]);
    }
}
