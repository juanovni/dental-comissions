<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Services\SocialRoiService;

class ApexSocialAppointmentStatusChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 34;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $maxHeight = '320px';

    protected ?string $heading = 'Citas por estado';

    protected ?string $description = 'Distribucion de citas atribuidas a redes sociales.';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->appointmentStatusData($this->getWidgetPeriodFilters());

        return $this->baseApexOptions([
            'chart' => [
                'height' => 320,
                'type' => 'donut',
            ],
            'colors' => ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#6b7280'],
            'labels' => $data['labels'],
            'series' => $data['values'],
            'legend' => [
                'fontSize' => '12px',
                'markers' => ['size' => 6],
                'position' => 'bottom',
                'show' => true,
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => ['fontSize' => '11px'],
                'dropShadow' => ['enabled' => false],
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '55%',
                        'labels' => [
                            'show' => true,
                            'total' => [
                                'show' => true,
                                'label' => 'Total',
                                'fontSize' => '13px',
                            ],
                        ],
                    ],
                ],
            ],
            'responsive' => [
                [
                    'breakpoint' => 480,
                    'options' => [
                        'chart' => ['width' => 280],
                        'legend' => ['position' => 'bottom'],
                    ],
                ],
            ],
        ]);
    }
}
