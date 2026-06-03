<?php

namespace App\Filament\Resources\Procedures;

use App\Filament\Resources\Procedures\Pages\CreateProcedure;
use App\Filament\Resources\Procedures\Pages\EditProcedure;
use App\Filament\Resources\Procedures\Pages\ListProcedures;
use App\Models\Procedure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProcedureResource extends Resource
{
    protected static ?string $model = Procedure::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Procedimientos';

    protected static ?string $modelLabel = 'procedimiento';

    protected static ?string $pluralModelLabel = 'procedimientos';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('code')->label('Codigo')->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('category')->label('Categoria')->maxLength(255),
            TextInput::make('internal_rate')->label('Tarifa interna')->numeric()->prefix('$'),
            Toggle::make('is_active')->label('Activo')->default(true),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('code')->label('Codigo')->searchable(),
                TextColumn::make('category')->label('Categoria')->searchable()->sortable(),
                TextColumn::make('internal_rate')->label('Tarifa interna')->money('USD')->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcedures::route('/'),
            'create' => CreateProcedure::route('/create'),
            'edit' => EditProcedure::route('/{record}/edit'),
        ];
    }
}
