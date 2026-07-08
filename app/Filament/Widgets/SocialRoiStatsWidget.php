<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiWidgetPeriod;
use App\Services\SocialRoiService;
use App\Support\SocialRoiPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class SocialRoiStatsWidget extends StatsOverviewWidget
{
    use HasSocialRoiWidgetPeriod;

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.social-roi-stats-widget';

    protected ?string $heading = 'ROI Social';

    protected ?string $description = 'Atribucion desde comentario social hasta actividad clinica.';

    public function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $service = app(SocialRoiService::class);
        $period = $this->getWidgetPeriod();
        $filters = $this->getWidgetPeriodFilters();
        $summary = $service->summary($filters);
        $previousSummary = $service->summary([
            'period' => 'custom',
            'from' => $period['previous_from_date'],
            'until' => $period['previous_until_date'],
        ]);

        $previousPeriodLabel = $period['previous_label'];

        $revenue = (float) ($summary['revenue'] ?? 0);
        $prevRevenue = (float) ($previousSummary['revenue'] ?? 0);
        $pipeline = (float) ($summary['pipeline_value'] ?? 0);
        $prevPipeline = (float) ($previousSummary['pipeline_value'] ?? 0);
        $rate = (float) ($summary['lead_to_activity_rate'] ?? 0);
        $prevRate = (float) ($previousSummary['lead_to_activity_rate'] ?? 0);
        $leakage = (int) ($summary['leakage_count'] ?? 0);
        $prevLeakage = (int) ($previousSummary['leakage_count'] ?? 0);

        return [
            Stat::make('Ingresos atribuidos', $this->valueWithBadge(
                '$'.number_format($revenue, 2),
                $this->percentageTrend($revenue, $prevRevenue)
            ))
                ->description($this->previousValueDescription($previousPeriodLabel, '$'.number_format($prevRevenue, 2)))
                ->descriptionColor('gray')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Pipeline abierto', $this->valueWithBadge(
                '$'.number_format($pipeline, 2),
                $this->percentageTrend($pipeline, $prevPipeline)
            ))
                ->description($this->previousValueDescription($previousPeriodLabel, '$'.number_format($prevPipeline, 2)))
                ->descriptionColor('gray')
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Conversion social', $this->valueWithBadge(
                $rate.'%',
                $this->pointsTrend($rate, $prevRate)
            ))
                ->description($this->previousValueDescription($previousPeriodLabel, $prevRate.'%'))
                ->descriptionColor('gray')
                ->color($rate > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-presentation-chart-bar')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Fuga critica', $this->valueWithBadge(
                (string) $leakage,
                $this->percentageTrend($leakage, $prevLeakage)
            ))
                ->description($this->previousValueDescription($previousPeriodLabel, (string) $prevLeakage))
                ->descriptionColor('gray')
                ->color($leakage > 0 ? 'danger' : 'success')
                ->icon($leakage > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check')
                ->extraAttributes(['class' => 'social-roi-stat']),
        ];
    }

    private function valueWithBadge(string $value, array $trend): HtmlString
    {
        $value = e($value);
        $badge = e($trend['label']);
        $icon = $trend['change'] > 0 ? '&uarr;' : ($trend['change'] < 0 ? '&darr;' : '&rarr;');
        $statusClass = match ($trend['status']) {
            'success' => 'social-roi-trend-badge-success',
            'danger' => 'social-roi-trend-badge-danger',
            default => 'social-roi-trend-badge-neutral',
        };

        return new HtmlString(<<<HTML
<span class="social-roi-stat-value">
    <span>{$value}</span>
    <span class="social-roi-trend-badge {$statusClass}">
        <span>{$icon}</span>
        <span>{$badge}</span>
    </span>
</span>
HTML);
    }

    private function previousValueDescription(string $periodLabel, string $value): HtmlString
    {
        $periodLabel = e($periodLabel);
        $value = e($value);

        return new HtmlString(<<<HTML
<span class="social-roi-previous-value">
    Vs {$periodLabel}: <strong style="color: #111827; font-weight: 600;">{$value}</strong>
</span>
HTML);
    }

    private function percentageTrend(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            $change = (float) $current === 0.0 ? 0.0 : 100.0;
        } else {
            $change = (($current - $previous) / $previous) * 100;
        }

        return $this->trend(round($change, 1), '%');
    }

    private function pointsTrend(float|int $current, float|int $previous): array
    {
        $change = $current - $previous;

        return $this->trend(round($change, 1), ' pts');
    }

    private function trend(float $change, string $suffix): array
    {
        $status = $change === 0.0 ? 'gray' : ($change > 0 ? 'success' : 'danger');

        return [
            'change' => $change,
            'label' => ($change > 0 ? '+' : '').$change.$suffix,
            'status' => $status,
        ];
    }
}
