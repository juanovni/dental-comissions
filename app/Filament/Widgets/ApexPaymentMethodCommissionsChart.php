<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Models\ActivityRecord;

class ApexPaymentMethodCommissionsChart extends ApexChartWidget
{
    use HasApexChartDefaults;

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Comisiones por metodo de pago';

    protected ?string $description = 'Total pagado a doctores durante el mes actual.';

    protected function getOptions(): array
    {
        $rows = ActivityRecord::query()
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'activity_records.payment_method_id')
            ->whereBetween('activity_records.activity_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->selectRaw("coalesce(payment_methods.name, 'Sin metodo') as label, sum(activity_records.doctor_commission_amount) as total")
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->pluck('total', 'label');

        return $this->baseApexOptions([
            'chart' => [
                'height' => 300,
                'type' => 'donut',
            ],
            'colors' => ['#0f766e', '#0369a1', '#d97706', '#be123c', '#64748b'],
            'labels' => $rows->keys()->all(),
            'legend' => [
                'fontSize' => '12px',
                'markers' => ['size' => 6],
                'position' => 'bottom',
                'show' => true,
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '64%',
                    ],
                ],
            ],
            'series' => $rows->values()->map(fn ($value) => round((float) $value, 2))->all(),
            'stroke' => [
                'colors' => ['#ffffff'],
                'width' => 4,
            ],
        ]);
    }
}
