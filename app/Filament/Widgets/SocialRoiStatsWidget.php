<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasSocialRoiPeriod;
use App\Services\SocialRoiService;
use App\Support\SocialRoiPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class SocialRoiStatsWidget extends StatsOverviewWidget
{
    use HasSocialRoiPeriod;

    protected static ?int $sort = 30;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'ROI Social';

    protected ?string $description = 'Atribucion desde comentario social hasta actividad clinica.';

    protected function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getColumns(): int | array
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
        $summary = $service->summary($this->pageFilters);
        $period = SocialRoiPeriod::resolve($this->pageFilters);
        $previousSummary = $service->summary([
            'period' => 'custom',
            'from' => $period['previous_from_date'],
            'until' => $period['previous_until_date'],
        ]);

        return [
            Stat::make('Revenue social', $this->valueWithBadge(
                '$' . number_format($summary['revenue'], 2),
                $this->percentageTrend($summary['revenue'], $previousSummary['revenue'])
            ))
                ->description($this->previousValueDescription($period['comparison_label'], '$' . number_format($previousSummary['revenue'], 2)))
                ->descriptionColor('gray')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Conversion lead-actividad', $this->valueWithBadge(
                $summary['lead_to_activity_rate'] . '%',
                $this->pointsTrend($summary['lead_to_activity_rate'], $previousSummary['lead_to_activity_rate'])
            ))
                ->description($this->previousValueDescription($period['comparison_label'], $previousSummary['lead_to_activity_rate'] . '%'))
                ->descriptionColor('gray')
                ->color($summary['lead_to_activity_rate'] > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-arrow-trending-up')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Fuga +24h', $this->valueWithBadge(
                (string) $summary['leakage_count'],
                $this->percentageTrend($summary['leakage_count'], $previousSummary['leakage_count'])
            ))
                ->description($this->previousValueDescription($period['comparison_label'], (string) $previousSummary['leakage_count']))
                ->descriptionColor('gray')
                ->color($summary['leakage_count'] > 0 ? 'danger' : 'success')
                ->icon($summary['leakage_count'] > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Token sin WhatsApp', $this->valueWithBadge(
                (string) $summary['orphan_attribution_count'],
                $this->percentageTrend($summary['orphan_attribution_count'], $previousSummary['orphan_attribution_count'])
            ))
                ->description($this->previousValueDescription($period['comparison_label'], (string) $previousSummary['orphan_attribution_count']))
                ->descriptionColor('gray')
                ->color($summary['orphan_attribution_count'] > 0 ? 'warning' : 'success')
                ->icon($summary['orphan_attribution_count'] > 0 ? 'heroicon-o-link-slash' : 'heroicon-o-check-circle')
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
            'label' => ($change > 0 ? '+' : '') . $change . $suffix,
            'status' => $status,
        ];
    }
}
