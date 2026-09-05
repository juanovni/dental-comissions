<?php

namespace App\Filament\Resources\RecallTypes;

use App\Filament\Resources\RecallTypes\Pages\CreateRecallType;
use App\Filament\Resources\RecallTypes\Pages\EditRecallType;
use App\Filament\Resources\RecallTypes\Pages\ListRecallTypes;
use App\Models\RecallType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecallTypeResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('recall_types.view') ?? false;
    }

    protected static ?string $model = RecallType::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recall clinico y seguimiento';

    protected static ?string $navigationLabel = 'Tipos de recall';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'tipo de recall';

    protected static ?string $pluralModelLabel = 'tipos de recall';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Descripcion')
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(100),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('policyVersions_count')
                    ->label('Versiones')
                    ->counts('policyVersions')
                    ->sortable(),
                TextColumn::make('recallPlans_count')
                    ->label('Planes activos')
                    ->counts('recallPlans')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecallTypes::route('/'),
            'create' => CreateRecallType::route('/create'),
            'edit' => EditRecallType::route('/{record}/edit'),
        ];
    }
}
