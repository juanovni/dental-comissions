<?php

namespace App\Filament\Resources\ClinicalEncounters;

use App\Filament\Resources\ClinicalEncounters\Pages\CreateClinicalEncounter;
use App\Filament\Resources\ClinicalEncounters\Pages\EditClinicalEncounter;
use App\Filament\Resources\ClinicalEncounters\Pages\ListClinicalEncounters;
use App\Models\ClinicalEncounter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicalEncounterResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('clinical_encounters.view') ?? false;
    }

    protected static ?string $model = ClinicalEncounter::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion clinica';

    protected static ?string $navigationLabel = 'Encuentros Clinicos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'encuentro clinico';

    protected static ?string $pluralModelLabel = 'encuentros clinicos';

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
                Select::make('professional_id')
                    ->label('Profesional')
                    ->relationship('professional', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]),
            Grid::make(3)->schema([
                Select::make('encounter_type')
                    ->label('Tipo de encuentro')
                    ->options([
                        'consultation' => 'Consulta',
                        'treatment' => 'Tratamiento',
                        'follow_up' => 'Seguimiento',
                        'emergency' => 'Urgencia',
                        'walk_in' => 'Sin cita',
                        'recall' => 'Control programado',
                    ])
                    ->required(),
                Select::make('care_path')
                    ->label('Via de atencion')
                    ->options([
                        'planned' => 'Planificado',
                        'emergency' => 'Urgencia',
                        'walk_in' => 'Sin cita',
                        'recall' => 'Control programado',
                    ])
                    ->required(),
                DatePicker::make('occurred_at')
                    ->label('Fecha de atencion')
                    ->required(),
            ]),
            Grid::make(2)->schema([
                Select::make('clinical_case_id')
                    ->label('Caso clinico')
                    ->relationship('clinicalCase', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'signed' => 'Firmado',
                        'amended' => 'Enmendado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('draft')
                    ->required(),
            ]),
            Textarea::make('subjective_notes')
                ->label('Subjetivo (S)')
                ->placeholder('Motivo de consulta, sintomas, molestias del paciente...')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('objective_findings')
                ->label('Objetivo (O)')
                ->placeholder('Hallazgos clinicos, examen, pruebas diagnosticas...')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('assessment')
                ->label('Analisis (A)')
                ->placeholder('Diagnostico, impresion clinica...')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('plan_notes')
                ->label('Plan (P)')
                ->placeholder('Tratamiento, prescripciones, indicaciones...')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('encounter_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'consultation' => 'Consulta',
                        'treatment' => 'Tratamiento',
                        'follow_up' => 'Seguimiento',
                        'emergency' => 'Urgencia',
                        'walk_in' => 'Sin cita',
                        'recall' => 'Control',
                        default => $state,
                    }),
                TextColumn::make('professional.name')
                    ->label('Profesional')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'signed' => 'Firmado',
                        'amended' => 'Enmendado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'signed' => 'success',
                        'amended' => 'warning',
                        'cancelled' => 'danger',
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
            'index' => ListClinicalEncounters::route('/'),
            'create' => CreateClinicalEncounter::route('/create'),
            'edit' => EditClinicalEncounter::route('/{record}/edit'),
        ];
    }
}
