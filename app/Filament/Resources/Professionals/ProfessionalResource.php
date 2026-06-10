<?php

namespace App\Filament\Resources\Professionals;

use App\Enums\ProfessionalRole;
use App\Filament\Resources\Professionals\Pages\CreateProfessional;
use App\Filament\Resources\Professionals\Pages\EditProfessional;
use App\Filament\Resources\Professionals\Pages\ListProfessionals;
use App\Models\Professional;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProfessionalResource extends Resource
{
    protected static ?string $model = Professional::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Operación Clínica';

    protected static ?string $navigationLabel = 'Profesionales';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'profesional';

    protected static ?string $pluralModelLabel = 'profesionales';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Select::make('role')
                ->label('Rol')
                ->options([
                    ProfessionalRole::Doctor->value => 'Doctor',
                    ProfessionalRole::Assistant->value => 'Auxiliar',
                ])
                ->required(),
            TextInput::make('whatsapp_phone')->label('WhatsApp')->tel()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('email')->label('Email')->email()->maxLength(255),
            Toggle::make('is_active')->label('Activo')->default(true),
            Toggle::make('can_register_via_whatsapp')->label('Puede registrar por WhatsApp')->default(true),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('role')->label('Rol')->badge()->formatStateUsing(fn (ProfessionalRole $state): string => $state === ProfessionalRole::Doctor ? 'Doctor' : 'Auxiliar'),
                TextColumn::make('whatsapp_phone')->label('WhatsApp')->searchable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                IconColumn::make('can_register_via_whatsapp')->label('WhatsApp activo')->boolean(),
                TextColumn::make('created_at')->label('Creado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rol')->options([
                    ProfessionalRole::Doctor->value => 'Doctor',
                    ProfessionalRole::Assistant->value => 'Auxiliar',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfessionals::route('/'),
            'create' => CreateProfessional::route('/create'),
            'edit' => EditProfessional::route('/{record}/edit'),
        ];
    }
}
