<?php

namespace App\Filament\Widgets;

use App\Services\SocialRoiService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SocialRoiStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 30;

    protected ?string $heading = 'ROI Social';

    protected ?string $description = 'Atribucion desde comentario social hasta actividad clinica.';

    protected function getStats(): array
    {
        $summary = app(SocialRoiService::class)->summary();

        return [
            Stat::make('Revenue social', '$' . number_format($summary['revenue'], 2))
                ->description($summary['activity_count'] . ' actividades atribuidas')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Conversion lead-actividad', $summary['lead_to_activity_rate'] . '%')
                ->description($summary['lead_count'] . ' leads / ' . $summary['activity_count'] . ' actividades')
                ->color($summary['lead_to_activity_rate'] > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-arrow-trending-up'),
            Stat::make('Fuga +24h', $summary['leakage_count'])
                ->description('Leads sin WhatsApp ni ficha')
                ->color($summary['leakage_count'] > 0 ? 'danger' : 'success')
                ->icon($summary['leakage_count'] > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check'),
        ];
    }
}
