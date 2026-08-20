<?php

namespace App\Filament\Resources\Clinics;

use App\Enums\TenantStatus;
use App\Filament\Resources\Clinics\RelationManagers\UsersRelationManager;
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
            Select::make('country')
                ->label('País')
                ->searchable()
                ->default('Ecuador')
                ->options([
                    'Ecuador' => 'Ecuador',
                    'Colombia' => 'Colombia',
                    'Perú' => 'Perú',
                    'México' => 'México',
                    'Chile' => 'Chile',
                    'Argentina' => 'Argentina',
                    'Brasil' => 'Brasil',
                    'Venezuela' => 'Venezuela',
                    'Bolivia' => 'Bolivia',
                    'Paraguay' => 'Paraguay',
                    'Uruguay' => 'Uruguay',
                    'Panamá' => 'Panamá',
                    'Costa Rica' => 'Costa Rica',
                    'Guatemala' => 'Guatemala',
                    'Honduras' => 'Honduras',
                    'Nicaragua' => 'Nicaragua',
                    'El Salvador' => 'El Salvador',
                    'Cuba' => 'Cuba',
                    'República Dominicana' => 'República Dominicana',
                    'Puerto Rico' => 'Puerto Rico',
                    'Estados Unidos' => 'Estados Unidos',
                    'España' => 'España',
                ]),
            Select::make('currency')
                ->label('Moneda')
                ->searchable()
                ->required()
                ->default('USD')
                ->options([
                    'USD' => 'USD - Dólar estadounidense',
                    'EUR' => 'EUR - Euro',
                    'MXN' => 'MXN - Peso mexicano',
                    'COP' => 'COP - Peso colombiano',
                    'PEN' => 'PEN - Sol peruano',
                    'CLP' => 'CLP - Peso chileno',
                    'ARS' => 'ARS - Peso argentino',
                    'BRL' => 'BRL - Real brasileño',
                    'VES' => 'VES - Bolívar venezolano',
                    'BOB' => 'BOB - Boliviano',
                    'PYG' => 'PYG - Guaraní paraguayo',
                    'UYU' => 'UYU - Peso uruguayo',
                    'CRC' => 'CRC - Colón costarricense',
                    'GTQ' => 'GTQ - Quetzal guatemalteco',
                    'HNL' => 'HNL - Lempira hondureño',
                    'NIO' => 'NIO - Córdoba nicaragüense',
                    'SVC' => 'SVC - Colón salvadoreño',
                    'CUP' => 'CUP - Peso cubano',
                    'DOP' => 'DOP - Peso dominicano',
                    'GBP' => 'GBP - Libra esterlina',
                ]),
            Select::make('timezone')
                ->label('Zona horaria')
                ->searchable()
                ->required()
                ->default('America/Guayaquil')
                ->options([
                    'America/Guayaquil' => 'Ecuador (America/Guayaquil) UTC -5',
                    'America/New_York' => 'Nueva York (UTC -5/-4)',
                    'America/Mexico_City' => 'Ciudad de México (UTC -6/-5)',
                    'America/Bogota' => 'Colombia (UTC -5)',
                    'America/Lima' => 'Perú (UTC -5)',
                    'America/Santiago' => 'Chile (UTC -4/-3)',
                    'America/Argentina/Buenos_Aires' => 'Argentina (UTC -3)',
                    'America/Sao_Paulo' => 'Brasil (UTC -3/-2)',
                    'America/Caracas' => 'Venezuela (UTC -4)',
                    'America/Panama' => 'Panamá (UTC -5)',
                    'America/Costa_Rica' => 'Costa Rica (UTC -6)',
                    'America/Guatemala' => 'Guatemala (UTC -6)',
                    'America/Tegucigalpa' => 'Honduras (UTC -6)',
                    'America/Managua' => 'Nicaragua (UTC -6)',
                    'America/El_Salvador' => 'El Salvador (UTC -6)',
                    'America/La_Paz' => 'Bolivia (UTC -4)',
                    'America/Asuncion' => 'Paraguay (UTC -4/-3)',
                    'America/Havana' => 'Cuba (UTC -5/-4)',
                    'America/Santo_Domingo' => 'República Dominicana (UTC -4)',
                    'America/Puerto_Rico' => 'Puerto Rico (UTC -4)',
                    'Europe/Madrid' => 'España (UTC +1/+2)',
                    'Europe/London' => 'Reino Unido (UTC +0/+1)',
                    'America/Los_Angeles' => 'Los Ángeles (UTC -8/-7)',
                    'America/Chicago' => 'Chicago (UTC -6/-5)',
                    'UTC' => 'UTC',
                ]),
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

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }
}
