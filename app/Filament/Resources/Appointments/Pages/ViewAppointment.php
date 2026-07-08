<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Detalles de la cita')
                ->columns(3)
                ->schema([
                    TextEntry::make('patient.full_name')
                        ->label('Paciente')
                        ->placeholder('Sin paciente asignado'),
                    TextEntry::make('doctor.name')
                        ->label('Doctor')
                        ->placeholder('Sin doctor asignado'),
                    TextEntry::make('procedure.name')
                        ->label('Procedimiento')
                        ->placeholder('Sin procedimiento'),
                    TextEntry::make('scheduled_at')
                        ->label('Fecha y hora')
                        ->dateTime(),
                    TextEntry::make('duration_minutes')
                        ->label('Duracion')
                        ->formatStateUsing(fn (?int $state): string => $state ? "{$state} minutos" : '-'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (mixed $state): ?string => $state?->label())
                        ->color(fn (mixed $state): ?string => $state?->color()),
                    TextEntry::make('source')
                        ->label('Origen')
                        ->badge()
                        ->formatStateUsing(fn (mixed $state): ?string => $state?->label()),
                    TextEntry::make('notes')
                        ->label('Notas')
                        ->placeholder('Sin notas')
                        ->columnSpanFull(),
                ]),
            Section::make('Integracion con calendario')
                ->columns(3)
                ->schema([
                    IconEntry::make('is_synced')
                        ->label('Sincronizada')
                        ->state(fn ($record): bool => $record->isSynced())
                        ->boolean(),
                    TextEntry::make('external_appointment_id')
                        ->label('ID evento Google')
                        ->placeholder('No sincronizado'),
                    TextEntry::make('last_synced_at')
                        ->label('Ultima sincronizacion')
                        ->placeholder('Nunca')
                        ->dateTime(),
                    TextEntry::make('sync_error')
                        ->label('Error de sync')
                        ->placeholder('Sin errores')
                        ->color('danger'),
                ]),
            Section::make('Origen social')
                ->columns(3)
                ->schema([
                    TextEntry::make('socialComment.socialIdentity.display_name')
                        ->label('Nombre del lead'),
                    TextEntry::make('socialComment.socialIdentity.phone')
                        ->label('Telefono del lead'),
                    TextEntry::make('socialComment.comment_text')
                        ->label('Comentario original')
                        ->limit(200)
                        ->columnSpanFull(),
                ]),
            Section::make('Linea de tiempo')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creada')
                        ->dateTime(),
                    TextEntry::make('confirmed_at')
                        ->label('Confirmada')
                        ->placeholder('Aun no confirmada')
                        ->dateTime(),
                    TextEntry::make('completed_at')
                        ->label('Completada')
                        ->placeholder('Aun no completada')
                        ->dateTime(),
                    TextEntry::make('cancelled_at')
                        ->label('Cancelada')
                        ->placeholder('No cancelada')
                        ->dateTime(),
                ]),
        ])->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->url(fn ($record): string => AppointmentResource::getUrl('edit', ['record' => $record])),
            ...AppointmentResource::appointmentActions(),
        ];
    }
}
