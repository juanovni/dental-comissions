<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApexSocialAppointmentStatusChart;
use App\Filament\Widgets\ApexSocialConversionFunnelChart;
use App\Filament\Widgets\ApexSocialPipelineValueChart;
use App\Filament\Widgets\ApexSocialPlatformPerformanceChart;
use App\Filament\Widgets\ApexSocialResponseTimeRoiChart;
use App\Filament\Widgets\SocialRoiRemindersWidget;
use App\Filament\Widgets\SocialRoiStatsWidget;
use App\Support\SocialRoiPeriod;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class DashboardRoiSocial extends BaseDashboard
{
    protected static string $routePath = '/roi-social';

    protected static ?string $title = 'Dashboard ROI Social';

    protected static ?string $navigationLabel = 'ROI Social';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $period = SocialRoiPeriod::resolve(['period' => '3_months']);
        $badgeLabel = $this->formatShortDate($period['from']).' - '.$this->formatShortDate($period['until']);

        return new HtmlString(<<<HTML
<span class="text-sm font-normal text-muted-foreground">
    Atribucion desde comentario social hasta actividad clinica.
    <span class="social-roi-period-chip" tabindex="0">
        {$badgeLabel}
        <span class="social-roi-period-info">i</span>
        <span class="social-roi-period-tooltip">
            <strong>Periodo actual</strong>
            <span>{$period['label']}</span>
            <strong>Compara con</strong>
            <span>{$period['previous_label']}</span>
        </span>
    </span>
</span>
HTML);
    }

    private function formatShortDate(mixed $date): string
    {
        return str($date->translatedFormat('j M'))->replace('.', '')->toString();
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            SocialRoiStatsWidget::class,
            SocialRoiRemindersWidget::class,
            ApexSocialConversionFunnelChart::class,
            ApexSocialAppointmentStatusChart::class,
            ApexSocialPipelineValueChart::class,
            ApexSocialResponseTimeRoiChart::class,
            ApexSocialPlatformPerformanceChart::class,
        ];
    }
}
