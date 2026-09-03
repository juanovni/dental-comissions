<?php

namespace App\Filament\Resources\PerformedProcedures;

use App\Filament\Resources\PerformedProcedures\Pages\CreatePerformedProcedure;
use App\Filament\Resources\PerformedProcedures\Pages\EditPerformedProcedure;
use App\Filament\Resources\PerformedProcedures\Pages\ListPerformedProcedures;
use App\Models\PerformedProcedure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerformedProcedureResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('performed_procedures.view') ?? false;
    }

    protected static ?string $model = PerformedProcedure::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion clinica';

    protected static ?string $navigationLabel = 'Procedimientos Realizados';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'procedimiento realizado';

    protected static ?string $pluralModelLabel = 'procedimientos realizados';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('patient_id')
                    ->label('Paciente')
                    ->relationship('patient', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('clinical_encounter_id')
                    ->label('Encuentro clinico')
                    ->relationship('encounter', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]),
            Grid::make(3)->schema([
                Select::make('procedure_id')
                    ->label('Procedimiento')
                    ->relationship('procedure', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('performed_by')
                    ->label('Realizado por')
                    ->relationship('performedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'amended' => 'Enmendado',
                    ])
                    ->default('completed')
                    ->required(),
            ]),
            Grid::make(4)->schema([
                TextInput::make('tooth')
                    ->label('Pieza')
                    ->maxLength(10)
                    ->placeholder('Ej: 11'),
                TextInput::make('surface')
                    ->label('Superficie')
                    ->maxLength(20)
                    ->placeholder('Ej: Oclusal'),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                TextInput::make('procedure_name_snapshot')
                    ->label('Nombre del procedimiento')
                    ->maxLength(255)
                    ->dehydrated()
                    ->required(),
            ]),
            Grid::make(2)->schema([
                DatePicker::make('started_at')
                    ->label('Iniciado el'),
                DatePicker::make('completed_at')
                    ->label('Completado el'),
            ]),
            Textarea::make('clinical_notes')
                ->label('Notas clinicas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('completed_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('procedure_name_snapshot')
                    ->label('Procedimiento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tooth')
                    ->label('Pieza'),
                TextColumn::make('performedBy.name')
                    ->label('Realizado por')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'amended' => 'Enmendado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'amended' => 'warning',
                        default => 'gray',
                    }),
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
            'index' => ListPerformedProcedures::route('/'),
            'create' => CreatePerformedProcedure::route('/create'),
            'edit' => EditPerformedProcedure::route('/{record}/edit'),
        ];
    }
}
