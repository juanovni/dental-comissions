<?php

namespace App\Filament\Resources\ClinicalCases;

use App\Filament\Resources\ClinicalCases\Pages\CreateClinicalCase;
use App\Filament\Resources\ClinicalCases\Pages\EditClinicalCase;
use App\Filament\Resources\ClinicalCases\Pages\ListClinicalCases;
use App\Models\ClinicalCase;
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

class ClinicalCaseResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('clinical_cases.view') ?? false;
    }

    protected static ?string $model = ClinicalCase::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion clinica';

    protected static ?string $navigationLabel = 'Casos Clinicos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'caso clinico';

    protected static ?string $pluralModelLabel = 'casos clinicos';

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
                Select::make('responsible_professional_id')
                    ->label('Profesional responsable')
                    ->relationship('responsibleProfessional', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]),
            Grid::make(3)->schema([
                Select::make('case_type')
                    ->label('Tipo de caso')
                    ->options([
                        'orthodontics' => 'Ortodoncia',
                        'endodontics' => 'Endodoncia',
                        'periodontics' => 'Periodoncia',
                        'general' => 'General',
                        'implantology' => 'Implantologia',
                        'prosthodontics' => 'Prostodoncia',
                        'pediatric' => 'Odontopediatria',
                        'oral_surgery' => 'Cirugia oral',
                        'cosmetic' => 'Estetica dental',
                    ])
                    ->required(),
                Select::make('specialty_id')
                    ->label('Especialidad')
                    ->relationship('specialty', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'on_hold' => 'En espera',
                        'completed' => 'Completado',
                        'closed' => 'Cerrado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('draft')
                    ->required(),
            ]),
            TextInput::make('title')
                ->label('Titulo')
                ->required()
                ->maxLength(255),
            Textarea::make('diagnosis_summary')
                ->label('Resumen diagnostico')
                ->rows(3)
                ->columnSpanFull(),
            Grid::make(3)->schema([
                DatePicker::make('started_at')
                    ->label('Iniciado el'),
                DatePicker::make('completed_at')
                    ->label('Completado el'),
                DatePicker::make('closed_at')
                    ->label('Cerrado el'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('case_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'orthodontics' => 'Ortodoncia',
                        'endodontics' => 'Endodoncia',
                        'periodontics' => 'Periodoncia',
                        'general' => 'General',
                        'implantology' => 'Implantologia',
                        'prosthodontics' => 'Prostodoncia',
                        'pediatric' => 'Odontopediatria',
                        'oral_surgery' => 'Cirugia oral',
                        'cosmetic' => 'Estetica dental',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'on_hold' => 'En espera',
                        'completed' => 'Completado',
                        'closed' => 'Cerrado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'on_hold' => 'warning',
                        'completed' => 'info',
                        'closed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('responsibleProfessional.name')
                    ->label('Profesional')
                    ->searchable()
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
            'index' => ListClinicalCases::route('/'),
            'create' => CreateClinicalCase::route('/create'),
            'edit' => EditClinicalCase::route('/{record}/edit'),
        ];
    }
}
