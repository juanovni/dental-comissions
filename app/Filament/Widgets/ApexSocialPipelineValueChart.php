<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasApexChartDefaults;
use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Services\SocialRoiService;

class ApexSocialPipelineValueChart extends ApexChartWidget
{
    use HasApexChartDefaults;
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 35;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    protected ?string $heading = 'Valor estimado por etapa';

    protected ?string $description = 'Pipeline comercial medido en USD por estado.';

    protected ?string $maxHeight = '270px';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getOptions(): array
    {
        $data = app(SocialRoiService::class)->pipelineValueByStage($this->getWidgetPeriodFilters());

        return $this->baseApexOptions([
            'chart' => [
                'height' => 270,
                'type' => 'bar',
            ],
            'colors' => ['#0f766e'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 9,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '52%',
                    'distributed' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Valor USD',
                    'data' => $data['values'],
                ],
            ],
            'xaxis' => [
                'categories' => $data['labels'],
                'axisBorder' => ['show' => false],
                'axisTicks' => ['show' => false],
            ],
            'yaxis' => ['title' => ['text' => 'USD']],
        ]);
    }
}
