<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActivityStatusChart;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\DoctorCommissionsTable;
use App\Filament\Widgets\PaymentMethodCommissionsChart;
use App\Filament\Widgets\TopDoctorsChart;
use App\Filament\Widgets\TopProceduresChart;
use App\Filament\Widgets\WhatsappIssuesTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard operativo';

    protected static ?string $navigationLabel = 'Actividad y comisiones';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Panel administrativo';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
            ActivityStatusChart::class,
            TopProceduresChart::class,
            TopDoctorsChart::class,
            PaymentMethodCommissionsChart::class,
            DoctorCommissionsTable::class,
            WhatsappIssuesTable::class,
        ];
    }
}
