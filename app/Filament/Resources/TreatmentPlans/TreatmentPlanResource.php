<?php

namespace App\Filament\Resources\TreatmentPlans;

use App\Filament\Resources\TreatmentPlans\Pages\CreateTreatmentPlan;
use App\Filament\Resources\TreatmentPlans\Pages\EditTreatmentPlan;
use App\Filament\Resources\TreatmentPlans\Pages\ListTreatmentPlans;
use App\Models\TreatmentPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreatmentPlanResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('treatment_plans.view') ?? false;
    }

    protected static ?string $model = TreatmentPlan::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion clinica';

    protected static ?string $navigationLabel = 'Planes de Tratamiento';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'plan de tratamiento';

    protected static ?string $pluralModelLabel = 'planes de tratamiento';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('patient_id')
                ->label('Paciente')
                ->relationship('patient', 'full_name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('title')
                ->label('Titulo')
                ->required()
                ->maxLength(255),
            Select::make('responsible_professional_id')
                ->label('Profesional responsable')
                ->relationship('responsibleProfessional', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('clinical_priority')
                ->label('Prioridad clinica')
                ->options([
                    'low' => 'Baja',
                    'normal' => 'Normal',
                    'high' => 'Alta',
                    'urgent' => 'Urgente',
                ])
                ->default('normal')
                ->required(),
            DatePicker::make('valid_until')
                ->label('Vigente hasta'),
            Textarea::make('diagnosis_summary')
                ->label('Resumen diagnostico')
                ->rows(3)
                ->columnSpanFull(),

            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('procedure_id')
                        ->label('Procedimiento')
                        ->relationship('procedure', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('tooth')
                        ->label('Pieza')
                        ->maxLength(10)
                        ->placeholder('Ej: 11'),
                    TextInput::make('surface')
                        ->label('Superficie')
                        ->maxLength(20)
                        ->placeholder('Ej: Oclusal'),
                    Select::make('clinical_priority')
                        ->label('Prioridad')
                        ->options([
                            'low' => 'Baja',
                            'normal' => 'Normal',
                            'high' => 'Alta',
                            'urgent' => 'Urgente',
                        ])
                        ->default('normal'),
                    TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),
                    TextInput::make('duration_minutes')
                        ->label('Duracion (min)')
                        ->numeric()
                        ->default(30)
                        ->minValue(1),
                    TextInput::make('sessions')
                        ->label('Sesiones')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),
                    Select::make('specialty_id')
                        ->label('Especialidad')
                        ->relationship('specialty', 'name')
                        ->searchable()
                        ->preload(),
                    Textarea::make('clinical_hold_reason')
                        ->label('Razon de bloqueo clinico')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->addActionLabel('Agregar procedimiento')
                ->defaultItems(1)
                ->collapsible(),

            TextInput::make('currency')
                ->label('Moneda')
                ->default('USD')
                ->maxLength(3)
                ->disabled(),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('responsibleProfessional.name')
                    ->label('Profesional')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lifecycle_status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'pending_approval' => 'Pendiente',
                        'active' => 'Activo',
                        'superseded' => 'Reemplazado',
                        'administratively_closed' => 'Cerrado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'active' => 'success',
                        'superseded' => 'info',
                        'administratively_closed' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('commercial_status')
                    ->label('Comercial')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_sent' => 'No enviado',
                        'sent' => 'Enviado',
                        'viewed' => 'Visto',
                        'partially_accepted' => 'Parcial',
                        'accepted' => 'Aceptado',
                        'rejected' => 'Rechazado',
                        'expired' => 'Vencido',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'not_sent' => 'gray',
                        'sent' => 'info',
                        'viewed' => 'info',
                        'partially_accepted' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('clinical_status')
                    ->label('Clinico')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_started' => 'No iniciado',
                        'in_progress' => 'En progreso',
                        'partially_completed' => 'Parcial',
                        'completed' => 'Completado',
                        'on_hold' => 'En espera',
                        'unresolved_needs' => 'Pendencias',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'not_started' => 'gray',
                        'in_progress' => 'info',
                        'partially_completed' => 'warning',
                        'completed' => 'success',
                        'on_hold' => 'warning',
                        'unresolved_needs' => 'danger',
                        default => 'gray',
                    }),
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
            'index' => ListTreatmentPlans::route('/'),
            'create' => CreateTreatmentPlan::route('/create'),
            'edit' => EditTreatmentPlan::route('/{record}/edit'),
        ];
    }
}
