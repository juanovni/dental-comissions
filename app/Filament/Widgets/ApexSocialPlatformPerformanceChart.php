<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiPeriod;
use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Services\SocialRoiService;

class ApexSocialPlatformPerformanceChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiPeriod;

    protected static ?int $sort = 33;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Rendimiento por red social';

    protected ?string $description = 'Captacion vs fidelizacion e ingreso atribuido por plataforma.';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected ?string $maxHeight = '360px';

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->platformPerformanceData($this->pageFilters);

        return $this->baseApexOptions([
            'chart' => [
                'height' => 360,
                'stacked' => true,
                'type' => 'line',
            ],
            'colors' => ['#6366f1', '#818cf8', '#10b981'],
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
                    'columnWidth' => '44%',
                ],
            ],
            'series' => [
                [
                    'name' => 'Nuevas (captacion)',
                    'type' => 'bar',
                    'data' => $data['new_leads'],
                ],
                [
                    'name' => 'Recurrentes (pacientes)',
                    'type' => 'bar',
                    'data' => $data['recurring_patients'],
                ],
                [
                    'name' => 'Revenue',
                    'type' => 'line',
                    'data' => $data['revenue'],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [0, 0, 4],
            ],
            'xaxis' => [
                'categories' => $data['labels'],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
                'labels' => [
                    'style' => [
                        'colors' => '#374151',
                        'fontSize' => '12px',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                [
                    'decimalsInFloat' => 0,
                    'seriesName' => 'Nuevas (captacion)',
                    'title' => ['text' => 'Leads'],
                ],
                [
                    'decimalsInFloat' => 0,
                    'seriesName' => 'Recurrentes (pacientes)',
                    'show' => false,
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
