<?php

namespace App\Filament\Widgets\Concerns;

trait HasApexChartDefaults
{
    protected function baseApexOptions(array $overrides = []): array
    {
        return array_replace_recursive([
            'chart' => [
                'fontFamily' => 'Aptos, Avenir Next, Instrument Sans, sans-serif',
                'toolbar' => ['show' => false],
                'zoom' => ['enabled' => false],
            ],
            'dataLabels' => ['enabled' => false],
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
            'legend' => ['show' => false],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'theme' => ['mode' => 'light'],
            'tooltip' => [
                'theme' => 'light',
                'style' => ['fontSize' => '12px'],
            ],
        ], $overrides);
    }
}
