<?php

namespace App\Filament\Widgets;

use App\Enums\ActivityStatus;
use App\Enums\WhatsappMessageStatus;
use App\Models\ActivityRecord;
use App\Models\WhatsappMessage;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Resumen operativo';

    protected ?string $description = 'Indicadores clave para controlar actividad, aprobaciones y pagos.';

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $weekEnd = now()->endOfWeek()->toDateString();
        $previousWeekStart = now()->subWeek()->startOfWeek()->toDateString();
        $previousWeekEnd = now()->subWeek()->endOfWeek()->toDateString();

        $todayActivities = ActivityRecord::whereDate('activity_date', $today)->count();
        $yesterdayActivities = ActivityRecord::whereDate('activity_date', $yesterday)->count();
        $weekActivities = ActivityRecord::whereBetween('activity_date', [$weekStart, $weekEnd])->count();
        $previousWeekActivities = ActivityRecord::whereBetween('activity_date', [$previousWeekStart, $previousWeekEnd])->count();
        $weekCommission = ActivityRecord::whereBetween('activity_date', [$weekStart, $weekEnd])
            ->sum('doctor_commission_amount');
        $previousWeekCommission = ActivityRecord::whereBetween('activity_date', [$previousWeekStart, $previousWeekEnd])
            ->sum('doctor_commission_amount');
        $pendingActivities = ActivityRecord::whereIn('status', [
            ActivityStatus::PendingConfirmation,
            ActivityStatus::NeedsReview,
        ])->count();
        $failedMessages = WhatsappMessage::where('status', WhatsappMessageStatus::Failed)->count();
        $monthPatients = ActivityRecord::whereBetween('activity_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ])->distinct('patient_id')->count('patient_id');

        return [
            Stat::make('Actividades hoy', $todayActivities)
                ->description($this->trendDescription($todayActivities, $yesterdayActivities, 'vs ayer'))
                ->descriptionIcon($todayActivities >= $yesterdayActivities ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', IconPosition::Before)
                ->descriptionColor($todayActivities >= $yesterdayActivities ? 'success' : 'gray')
                ->color('primary')
                ->chart([2, 4, 3, 6, 5, $yesterdayActivities, $todayActivities])
                ->icon('heroicon-o-clipboard-document-check'),
            Stat::make('Actividades semana', $weekActivities)
                ->description($this->trendDescription($weekActivities, $previousWeekActivities, 'vs semana anterior'))
                ->descriptionIcon($weekActivities >= $previousWeekActivities ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', IconPosition::Before)
                ->descriptionColor($weekActivities >= $previousWeekActivities ? 'success' : 'gray')
                ->color('info')
                ->chart([3, 5, 4, 6, 8, 7, $weekActivities])
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Comision semanal', '$' . number_format((float) $weekCommission, 2))
                ->description($this->trendDescription((float) $weekCommission, (float) $previousWeekCommission, 'vs semana anterior'))
                ->descriptionIcon($weekCommission >= $previousWeekCommission ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', IconPosition::Before)
                ->descriptionColor($weekCommission >= $previousWeekCommission ? 'success' : 'gray')
                ->color('success')
                ->chart([120, 240, 180, 360, 310, (float) $previousWeekCommission, (float) $weekCommission])
                ->icon('heroicon-o-banknotes'),
            Stat::make('Pendientes', $pendingActivities)
                ->description($pendingActivities > 0 ? 'Requieren decision administrativa' : 'Flujo limpio, sin bloqueos')
                ->descriptionIcon($pendingActivities > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle', IconPosition::Before)
                ->color($pendingActivities > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
            Stat::make('Mensajes rechazados', $failedMessages)
                ->description($failedMessages > 0 ? 'Revisar WhatsApp y metodo de pago' : 'Sin fallos de interpretacion')
                ->descriptionIcon($failedMessages > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check', IconPosition::Before)
                ->color($failedMessages > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Pacientes del mes', $monthPatients)
                ->description('Pacientes unicos atendidos')
                ->color('gray')
                ->chart([1, 2, 2, 4, 3, 5, $monthPatients])
                ->icon('heroicon-o-identification'),
        ];
    }

    private function trendDescription(float|int $current, float|int $previous, string $suffix): string
    {
        if ($previous <= 0) {
            return $current > 0 ? "Nuevo movimiento {$suffix}" : "Sin movimiento {$suffix}";
        }

        $percentage = (($current - $previous) / $previous) * 100;

        return sprintf('%+.0f%% %s', $percentage, $suffix);
    }
}
