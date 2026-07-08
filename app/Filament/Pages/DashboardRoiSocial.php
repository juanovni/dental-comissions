<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApexSocialConversionFunnelChart;
use App\Filament\Widgets\ApexSocialLostReasonsChart;
use App\Filament\Widgets\ApexSocialPipelineValueChart;
use App\Filament\Widgets\ApexSocialPlatformPerformanceChart;
use App\Filament\Widgets\ApexSocialProcedureConversionChart;
use App\Filament\Widgets\ApexSocialResponseTimeRoiChart;
use App\Filament\Widgets\ApexSocialTopPostsChart;
use App\Filament\Widgets\SocialRoiStatsWidget;
use App\Support\SocialRoiPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DashboardRoiSocial extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/roi-social';

    protected static ?string $title = 'Dashboard ROI Social';

    protected static ?string $navigationLabel = 'ROI Social';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'social-roi-filters-form'], merge: true)
            ->components([
                Grid::make([
                    'default' => 1,
                    'md' => 3,
                ])->schema([
                    Select::make('period')
                        ->label('Periodo')
                        ->options(SocialRoiPeriod::presets())
                        ->default('last_30_days')
                        ->live(),
                    DatePicker::make('from')
                        ->label('Desde')
                        ->default(now()->subDays(29)->toDateString())
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    DatePicker::make('until')
                        ->label('Hasta')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                ]),
            ]);
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
            ApexSocialPipelineValueChart::class,
            ApexSocialLostReasonsChart::class,
            ApexSocialPlatformPerformanceChart::class,
            ApexSocialProcedureConversionChart::class,
            ApexSocialResponseTimeRoiChart::class,
            ApexSocialConversionFunnelChart::class,
            ApexSocialTopPostsChart::class,
        ];
    }
}
