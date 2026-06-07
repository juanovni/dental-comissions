<?php

namespace App\Filament\Widgets;

use App\Models\ActivityRecord;
use Filament\Widgets\ChartWidget;

class PaymentMethodCommissionsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Comisiones por metodo de pago';

    protected ?string $description = 'Total pagado a doctores durante el mes actual.';

    protected function getData(): array
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

        return [
            'datasets' => [
                [
                    'label' => 'Comision',
                    'data' => $rows->values()->map(fn ($value) => round((float) $value, 2))->all(),
                    'backgroundColor' => ['#0f766e', '#0369a1', '#d97706', '#be123c', '#64748b'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 4,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '64%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['usePointStyle' => true, 'boxWidth' => 8],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
