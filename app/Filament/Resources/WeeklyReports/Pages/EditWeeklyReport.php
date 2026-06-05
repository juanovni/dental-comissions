<?php

namespace App\Filament\Resources\WeeklyReports\Pages;

use App\Enums\WeeklyReportStatus;
use App\Filament\Resources\WeeklyReports\WeeklyReportResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyReport extends EditRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === WeeklyReportStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading('Aprobar reporte')
                ->modalDescription('Esta accion marcara el reporte como aprobado y las actividades incluidas como pagadas.')
                ->action(function ($record) {
                    $record->approve();
                    $record->activities()->each(fn ($activity) => $activity->approve());
                    Notification::make()
                        ->title('Reporte aprobado')
                        ->success()
                        ->send();
                }),
            Action::make('mark_as_paid')
                ->label('Marcar como pagado')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn ($record) => $record->status === WeeklyReportStatus::Approved)
                ->requiresConfirmation()
                ->modalHeading('Marcar como pagado')
                ->modalDescription('Esta accion marcara el reporte y sus actividades como pagadas.')
                ->action(function ($record) {
                    $record->markAsPaid();
                    $record->activities()->each(fn ($activity) => $activity->markAsPaid());
                    Notification::make()
                        ->title('Reporte marcado como pagado')
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->visible(fn ($record) => $record->status === WeeklyReportStatus::Draft),
        ];
    }
}
