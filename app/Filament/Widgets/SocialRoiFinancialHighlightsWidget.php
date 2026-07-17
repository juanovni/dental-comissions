<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Services\SocialRoiService;
use Filament\Widgets\Widget;

class SocialRoiFinancialHighlightsWidget extends Widget
{
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 32;

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected string $view = 'filament.widgets.social-roi-financial-highlights-widget';

    protected ?string $heading = 'Ganancias';

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getPeriodBadgeLabel(): string
    {
        $period = $this->getWidgetPeriod();

        return $this->formatShortDate($period['from']).' - '.$this->formatShortDate($period['until']);
    }

    public function getCurrentPeriodLabel(): string
    {
        return $this->getWidgetPeriod()['label'];
    }

    public function getPreviousPeriodLabel(): string
    {
        return $this->getWidgetPeriod()['previous_label'];
    }

    public function getHighlightsData(): array
    {
        $service = app(SocialRoiService::class);
        $filters = $this->getWidgetPeriodFilters();
        $period = $this->getWidgetPeriod();
        $current = $service->financialHighlights($filters);
        $previous = $service->financialHighlights([
            'period' => 'custom',
            'from' => $period['previous_from_date'],
            'until' => $period['previous_until_date'],
        ]);

        return [
            'total_revenue' => $current['total_revenue'],
            'attributed_revenue' => $current['attributed_revenue'],
            'non_attributed_revenue' => $current['non_attributed_revenue'],
            'won_pipeline_value' => $current['won_pipeline_value'],
            'attribution_rate' => $current['attribution_rate'],
            'total_revenue_trend' => $this->percentageTrend($current['total_revenue'], $previous['total_revenue']),
            'attributed_trend' => $this->percentageTrend($current['attributed_revenue'], $previous['attributed_revenue']),
            'non_attributed_trend' => $this->percentageTrend($current['non_attributed_revenue'], $previous['non_attributed_revenue']),
            'won_pipeline_trend' => $this->percentageTrend($current['won_pipeline_value'], $previous['won_pipeline_value']),
        ];
    }

    private function percentageTrend(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            $change = (float) $current === 0.0 ? 0.0 : 100.0;
        } else {
            $change = (($current - $previous) / $previous) * 100;
        }

        return $this->trend(round($change, 1));
    }

    private function trend(float $change): array
    {
        $status = $change === 0.0 ? 'neutral' : ($change > 0 ? 'success' : 'danger');

        return [
            'change' => $change,
            'label' => ($change > 0 ? '+' : '').$change.'%',
            'status' => $status,
        ];
    }

    private function formatShortDate(mixed $date): string
    {
        return str($date->translatedFormat('j M'))->replace('.', '')->toString();
    }
}
