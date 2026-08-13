<?php

namespace App\Filament\Resources\Clinics;

use App\Enums\TenantStatus;
use App\Filament\Resources\Clinics\Pages\CreateClinic;
use App\Filament\Resources\Clinics\Pages\EditClinic;
use App\Filament\Resources\Clinics\Pages\ListClinics;
use App\Models\Clinic;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicResource extends Resource
{
    protected static ?string $model = Clinic::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Clínicas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'clínica';

    protected static ?string $pluralModelLabel = 'clínicas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('subdomain')
                ->label('Subdominio')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('primary_domain')
                ->label('Dominio principal')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('country')
                ->label('País')
                ->maxLength(255),
            TextInput::make('currency')
                ->label('Moneda')
                ->required()
                ->maxLength(3),
            TextInput::make('timezone')
                ->label('Zona horaria')
                ->required()
                ->maxLength(255),
            Select::make('status')
                ->label('Estado')
                ->options(TenantStatus::options())
                ->required(),
            KeyValue::make('settings')
                ->label('Settings')
                ->keyLabel('Clave')
                ->valueLabel('Valor')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('subdomain')->label('Subdominio')->searchable(),
                TextColumn::make('primary_domain')->label('Dominio')->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label()),
                TextColumn::make('currency')->label('Moneda'),
                TextColumn::make('timezone')->label('Zona horaria')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinics::route('/'),
            'create' => CreateClinic::route('/create'),
            'edit' => EditClinic::route('/{record}/edit'),
        ];
    }
}
