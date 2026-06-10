<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SocialConversionFunnelChart;
use App\Filament\Widgets\SocialRoiStatsWidget;
use App\Filament\Widgets\SocialTopPostsChart;
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
            SocialConversionFunnelChart::class,
            SocialTopPostsChart::class,
        ];
    }
}
