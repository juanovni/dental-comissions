<?php

namespace App\Filament\Resources\ActivityRecords\Pages;

use App\Enums\ActivityStatus;
use App\Filament\Resources\ActivityRecords\ActivityRecordResource;
use App\Models\ActivityRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditActivityRecord extends EditRecord
{
    protected static string $resource = ActivityRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => in_array($record->status, [
                    ActivityStatus::PendingConfirmation,
                    ActivityStatus::NeedsReview,
                ]))
                ->requiresConfirmation()
                ->modalHeading('Aprobar actividad')
                ->modalDescription('Esta accion marcara la actividad como aprobada.')
                ->action(function ($record) {
                    $record->approve();
                    Notification::make()
                        ->title('Actividad aprobada')
                        ->success()
                        ->send();
                }),
            Action::make('mark_as_paid')
                ->label('Marcar como pagado')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn ($record) => $record->status === ActivityStatus::Approved)
                ->requiresConfirmation()
                ->modalHeading('Marcar como pagado')
                ->modalDescription('Esta accion marcara la actividad como pagada.')
                ->action(function ($record) {
                    $record->markAsPaid();
                    Notification::make()
                        ->title('Actividad marcada como pagada')
                        ->success()
                        ->send();
                }),
            Action::make('request_correction')
                ->label('Solicitar correccion')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, [
                    ActivityStatus::PendingConfirmation,
                    ActivityStatus::Approved,
                ]))
                ->form([
                    \Filament\Forms\Components\Textarea::make('correction_notes')
                        ->label('Motivo de la correccion')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function ($record, array $data) {
                    $record->requestCorrection($data['correction_notes']);
                    Notification::make()
                        ->title('Solicitud de correccion enviada')
                        ->warning()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var ActivityRecord $record */
        $record = $this->record;

        $assistantIds = $this->data['assistants'] ?? [];

        $record->assistants()->sync($assistantIds);

        $record->calculateCommissions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['assistants']);
        return $data;
    }
}
