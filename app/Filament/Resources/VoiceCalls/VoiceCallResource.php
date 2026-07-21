<?php

namespace App\Filament\Resources\VoiceCalls;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Filament\Resources\VoiceCalls\Pages\ListVoiceCalls;
use App\Filament\Resources\VoiceCalls\Pages\ViewVoiceCall;
use App\Models\VoiceCall;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VoiceCallResource extends Resource
{
    protected static ?string $model = VoiceCall::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Pity Voice';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Llamadas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'llamada';

    protected static ?string $pluralModelLabel = 'llamadas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->sortable(),
                TextColumn::make('from_phone')
                    ->label('De')
                    ->searchable(),
                TextColumn::make('to_phone')
                    ->label('Para')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->placeholder('No identificado')
                    ->searchable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (VoiceChannelType $state): string => $state->label()),
                TextColumn::make('provider')
                    ->label('Proveedor')
                    ->placeholder('-')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (VoiceCallStatus $state): string => $state->label())
                    ->color(fn (VoiceCallStatus $state): string => match ($state) {
                        VoiceCallStatus::Started => 'warning',
                        VoiceCallStatus::InProgress => 'info',
                        VoiceCallStatus::AppointmentScheduled => 'success',
                        VoiceCallStatus::HandoffRequired => 'danger',
                        VoiceCallStatus::Completed => 'gray',
                        VoiceCallStatus::Failed => 'danger',
                        VoiceCallStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('duration_seconds')
                    ->label('Duracion')
                    ->formatStateUsing(fn (?int $state): string => $state ? gmdate('i:s', $state) : '-'),
                TextColumn::make('appointment.id')
                    ->label('Cita')
                    ->placeholder('-')
                    ->url(fn (?int $state): ?string => $state ? route('filament.admin.resources.appointments.view', $state) : null),
                TextColumn::make('last_error')
                    ->label('Error')
                    ->color('danger')
                    ->placeholder('-')
                    ->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 30, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoiceCalls::route('/'),
            'view' => ViewVoiceCall::route('/{record}'),
        ];
    }
}
