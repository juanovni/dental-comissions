<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApexSocialConversionFunnelChart;
use App\Filament\Widgets\ApexSocialTopPostsChart;
use App\Filament\Widgets\SocialRoiStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class DashboardRoiSocial extends BaseDashboard
{
    protected static string $routePath = '/roi-social';

    protected static ?string $title = 'Dashboard ROI Social';

    protected static ?string $navigationLabel = 'ROI Social';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string | \UnitEnum | null $navigationGroup = 'Panel administrativo';

    protected static ?int $navigationSort = 2;

    public function getWidgets(): array
    {
        return [
            SocialRoiStatsWidget::class,
            ApexSocialConversionFunnelChart::class,
            ApexSocialTopPostsChart::class,
        ];
    }
}
