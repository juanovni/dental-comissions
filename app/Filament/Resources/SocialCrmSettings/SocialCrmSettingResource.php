<?php

namespace App\Filament\Resources\SocialCrmSettings;

use App\Filament\Resources\SocialCrmSettings\Pages\CreateSocialCrmSetting;
use App\Filament\Resources\SocialCrmSettings\Pages\EditSocialCrmSetting;
use App\Filament\Resources\SocialCrmSettings\Pages\ListSocialCrmSettings;
use App\Models\SocialCrmSetting;
use App\Services\SocialCrmSettingsService;
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
use Filament\Tables\Table;

class SocialCrmSettingResource extends Resource
{
    protected static ?string $model = SocialCrmSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Configuracion CRM';

    protected static ?string $modelLabel = 'configuracion CRM social';

    protected static ?string $pluralModelLabel = 'configuraciones CRM social';

    protected static ?int $navigationSort = 24;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('setting_group')
                ->label('Grupo')
                ->required()
                ->maxLength(255),
            TextInput::make('key')
                ->label('Clave')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('label')
                ->label('Etiqueta')
                ->required()
                ->maxLength(255),
            Select::make('value_type')
                ->label('Tipo')
                ->options([
                    'string' => 'Texto',
                    'integer' => 'Numero entero',
                    'boolean' => 'Booleano',
                    'array' => 'Lista/JSON',
                ])
                ->required()
                ->default('string'),
            Textarea::make('value')
                ->label('Valor JSON')
                ->helperText('Texto: "valor". Booleano: true/false. Lista: ["uno", "dos"]. Numero: 30.')
                ->formatStateUsing(fn (mixed $state): string => json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: 'null')
                ->dehydrateStateUsing(fn (string $state): mixed => json_decode($state, true))
                ->rules(['json'])
                ->required()
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('setting_group')->label('Grupo')->badge()->searchable()->sortable(),
                TextColumn::make('key')->label('Clave')->searchable()->sortable(),
                TextColumn::make('label')->label('Etiqueta')->searchable()->wrap(),
                TextColumn::make('value_type')->label('Tipo')->badge(),
                TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn (mixed $state): string => json_encode($state, JSON_UNESCAPED_UNICODE) ?: '')
                    ->limit(70)
                    ->wrap(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function afterSave(): void
    {
        app(SocialCrmSettingsService::class)->clearCache();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialCrmSettings::route('/'),
            'create' => CreateSocialCrmSetting::route('/create'),
            'edit' => EditSocialCrmSetting::route('/{record}/edit'),
        ];
    }
}
