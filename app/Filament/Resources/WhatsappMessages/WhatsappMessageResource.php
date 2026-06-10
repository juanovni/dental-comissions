<?php

namespace App\Filament\Resources\WhatsappMessages;

use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Filament\Resources\WhatsappMessages\Pages\ListWhatsappMessages;
use App\Models\WhatsappMessage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string | \UnitEnum | null $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Mensajes WhatsApp';

    protected static ?string $modelLabel = 'mensaje WhatsApp';

    protected static ?string $pluralModelLabel = 'mensajes WhatsApp';

    protected static ?int $navigationSort = 15;

    protected static bool $shouldRegisterNavigation = true;

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
                TextColumn::make('professional.name')
                    ->label('Doctor')
                    ->weight('semibold')
                    ->placeholder('No identificado')
                    ->searchable(),
                TextColumn::make('direction')
                    ->label('Direccion')
                    ->badge()
                    ->formatStateUsing(fn (WhatsappMessageDirection $state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (WhatsappMessageStatus $state): string => $state->label())
                    ->color(fn (WhatsappMessageStatus $state): string => $state->color()),
                TextColumn::make('from_phone')
                    ->label('Telefono')
                    ->searchable()
                    ->placeholder('N/A'),
                TextColumn::make('message_body')
                    ->label('Mensaje')
                    ->limit(60),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->color('danger')
                    ->placeholder('-')
                    ->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->label('Direccion')
                    ->options([
                        'incoming' => 'Entrante',
                        'outgoing' => 'Saliente',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(WhatsappMessageStatus::cases())
                        ->mapWithKeys(fn (WhatsappMessageStatus $s) => [$s->value => $s->label()])
                        ->toArray()),
            ])
            ->paginated([15, 30, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsappMessages::route('/'),
        ];
    }
}
