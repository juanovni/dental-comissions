<?php

namespace App\Filament\Resources\LocalLanguagePatterns;

use App\Enums\LocalLanguagePatternType;
use App\Filament\Resources\LocalLanguagePatterns\Pages\CreateLocalLanguagePattern;
use App\Filament\Resources\LocalLanguagePatterns\Pages\EditLocalLanguagePattern;
use App\Filament\Resources\LocalLanguagePatterns\Pages\ListLocalLanguagePatterns;
use App\Models\LocalLanguagePattern;
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

class LocalLanguagePatternResource extends Resource
{
    protected static ?string $model = LocalLanguagePattern::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Lenguaje local';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static ?int $navigationSort = 26;

    protected static ?string $modelLabel = 'patrón de lenguaje local';

    protected static ?string $pluralModelLabel = 'patrones de lenguaje local';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Tipo')
                ->options(self::typeOptions())
                ->required()
                ->native(false),
            TextInput::make('phrase')
                ->label('Frase')
                ->helperText('Ej.: tardecita, tiene chance, de una.')
                ->required()
                ->maxLength(255),
            TextInput::make('value')
                ->label('Valor')
                ->helperText('Ej.: afternoon, appointment_interest, confirmed.')
                ->required()
                ->maxLength(120),
            TextInput::make('locale')
                ->label('Locale')
                ->required()
                ->default('es_EC')
                ->maxLength(20),
            Select::make('source')
                ->label('Origen')
                ->options([
                    'manual' => 'Manual',
                    'observation' => 'Observación',
                    'system' => 'Sistema',
                ])
                ->default('manual')
                ->required()
                ->native(false),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
            TextInput::make('normalized_phrase')
                ->label('Frase normalizada')
                ->disabled()
                ->dehydrated(false),
            Textarea::make('metadata')
                ->label('Metadata JSON')
                ->formatStateUsing(fn (mixed $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '')
                ->dehydrateStateUsing(fn (?string $state): ?array => filled($state) ? json_decode($state, true) : null)
                ->rules(['nullable', 'json'])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (LocalLanguagePatternType|string|null $state): string => $state instanceof LocalLanguagePatternType ? $state->label() : (string) $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('phrase')->label('Frase')->searchable()->sortable(),
                TextColumn::make('value')->label('Valor')->badge()->searchable()->sortable(),
                TextColumn::make('locale')->label('Locale')->badge()->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('approver.name')->label('Aprobado por')->placeholder('Sistema')->toggleable(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(self::typeOptions()),
                SelectFilter::make('is_active')->label('Activo')->options([
                    true => 'Activo',
                    false => 'Inactivo',
                ]),
                SelectFilter::make('source')->label('Origen')->options([
                    'manual' => 'Manual',
                    'observation' => 'Observación',
                    'system' => 'Sistema',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocalLanguagePatterns::route('/'),
            'create' => CreateLocalLanguagePattern::route('/create'),
            'edit' => EditLocalLanguagePattern::route('/{record}/edit'),
        ];
    }

    private static function typeOptions(): array
    {
        return collect(LocalLanguagePatternType::cases())
            ->mapWithKeys(fn (LocalLanguagePatternType $type): array => [$type->value => $type->label()])
            ->all();
    }
}
