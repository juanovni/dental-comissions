<?php

namespace App\Filament\Resources\Specialties;

use App\Filament\Resources\Specialties\Pages\CreateSpecialty;
use App\Filament\Resources\Specialties\Pages\EditSpecialty;
use App\Filament\Resources\Specialties\Pages\ListSpecialties;
use App\Models\Specialty;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class SpecialtyResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('specialties.view') ?? false;
    }

    protected static ?string $model = Specialty::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuracion';

    protected static ?string $navigationLabel = 'Especialidades';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'especialidad';

    protected static ?string $pluralModelLabel = 'especialidades';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('clinic_id', Filament::getTenant()?->getKey()),
                ),
            TextInput::make('code')
                ->label('Codigo')
                ->maxLength(50)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('clinic_id', Filament::getTenant()?->getKey()),
                ),
            Toggle::make('is_active')->label('Activa')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('code')->label('Codigo')->searchable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecialties::route('/'),
            'create' => CreateSpecialty::route('/create'),
            'edit' => EditSpecialty::route('/{record}/edit'),
        ];
    }
}
