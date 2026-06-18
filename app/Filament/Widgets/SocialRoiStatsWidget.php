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

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'ROI Social';

    protected ?string $description = 'Atribucion desde comentario social hasta actividad clinica.';

    protected function getDescription(): ?string
    {
        return $this->socialRoiDescription($this->description);
    }

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 5,
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
            Stat::make('Dinero en pipeline', $this->valueWithBadge(
                '$'.number_format($summary['pipeline_value'], 2),
                $this->percentageTrend($summary['pipeline_value'], $previousSummary['pipeline_value'])
            ))
                ->description('Oportunidades abiertas estimadas en USD')
                ->descriptionColor('gray')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Ganado este mes', $this->valueWithBadge(
                '$'.number_format($summary['won_value_month'], 2),
                $this->percentageTrend($summary['won_value_month'], $previousSummary['won_value_month'])
            ))
                ->description('Valor estimado en etapa Ganado')
                ->descriptionColor('gray')
                ->color('info')
                ->icon('heroicon-o-trophy')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Perdidos +$1,000', $this->valueWithBadge(
                (string) $summary['high_value_lost_count'],
                $this->percentageTrend($summary['high_value_lost_count'], $previousSummary['high_value_lost_count'])
            ))
                ->description('Leads perdidos con presupuesto alto')
                ->descriptionColor('gray')
                ->color($summary['high_value_lost_count'] > 0 ? 'danger' : 'success')
                ->icon($summary['high_value_lost_count'] > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Smart Link -> WhatsApp', $this->valueWithBadge(
                $summary['smart_link_to_whatsapp_rate'].'%',
                $this->pointsTrend($summary['smart_link_to_whatsapp_rate'], $previousSummary['smart_link_to_whatsapp_rate'])
            ))
                ->description($this->previousValueDescription($period['comparison_label'], $previousSummary['smart_link_to_whatsapp_rate'].'%'))
                ->descriptionColor('gray')
                ->color($summary['smart_link_to_whatsapp_rate'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->extraAttributes(['class' => 'social-roi-stat']),
            Stat::make('Hot leads activos', $this->valueWithBadge(
                (string) $summary['active_hot_leads_count'],
                $this->percentageTrend($summary['active_hot_leads_count'], $previousSummary['active_hot_leads_count'])
            ))
                ->description('Interes alto sin cierre ganado/perdido')
                ->descriptionColor('gray')
                ->color($summary['active_hot_leads_count'] > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-fire')
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
