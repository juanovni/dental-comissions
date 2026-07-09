<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApexSocialAppointmentStatusChart;
use App\Filament\Widgets\ApexSocialConversionFunnelChart;
use App\Filament\Widgets\ApexSocialLostReasonsChart;
use App\Filament\Widgets\ApexSocialPipelineValueChart;
use App\Filament\Widgets\ApexSocialPlatformPerformanceChart;
use App\Filament\Widgets\ApexSocialResponseTimeRoiChart;
use App\Filament\Widgets\ApexSocialTopPostsChart;
use App\Filament\Widgets\SocialRoiRemindersWidget;
use App\Filament\Widgets\SocialRoiStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

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
            ApexSocialTopPostsChart::class,
            ApexSocialLostReasonsChart::class,
        ];
    }
}
