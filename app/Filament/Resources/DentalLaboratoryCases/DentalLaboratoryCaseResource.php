<?php

namespace App\Filament\Resources\DentalLaboratoryCases;

use App\Filament\Resources\DentalLaboratoryCases\Pages\CreateDentalLaboratoryCase;
use App\Filament\Resources\DentalLaboratoryCases\Pages\EditDentalLaboratoryCase;
use App\Filament\Resources\DentalLaboratoryCases\Pages\ListDentalLaboratoryCases;
use App\Models\DentalLaboratoryCase;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DentalLaboratoryCaseResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('laboratory_cases.view') ?? false;
    }

    protected static ?string $model = DentalLaboratoryCase::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Laboratorio dental';

    protected static ?string $navigationLabel = 'Casos de laboratorio';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'caso de laboratorio';

    protected static ?string $pluralModelLabel = 'casos de laboratorio';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('patient_id')
                ->label('Paciente')
                ->relationship('patient', 'full_name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('professional_id')
                ->label('Profesional responsable')
                ->relationship('professional', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('laboratory_name')
                ->label('Nombre del laboratorio')
                ->maxLength(255),
            Select::make('status')
                ->label('Estado')
                ->options([
                    'draft' => 'Borrador',
                    'ordered' => 'Ordenado',
                    'sent' => 'Enviado',
                    'accepted_by_lab' => 'Aceptado por laboratorio',
                    'in_production' => 'En produccion',
                    'received' => 'Recibido',
                    'quality_review' => 'Control de calidad',
                    'ready_for_patient' => 'Listo para paciente',
                    'adjustment_required' => 'Requiere ajuste',
                    'cancelled' => 'Cancelado',
                ])
                ->default('draft')
                ->required(),
            Select::make('priority')
                ->label('Prioridad')
                ->options([
                    'low' => 'Baja',
                    'normal' => 'Normal',
                    'high' => 'Alta',
                    'urgent' => 'Urgente',
                ])
                ->default('normal')
                ->required(),
            DatePicker::make('promised_date')
                ->label('Fecha prometida'),
            DatePicker::make('received_date')
                ->label('Fecha recibido'),
            DatePicker::make('ready_date')
                ->label('Fecha listo'),
            Textarea::make('clinical_notes')
                ->label('Notas clinicas')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('lab_instructions')
                ->label('Instrucciones al laboratorio')
                ->rows(3)
                ->columnSpanFull(),

            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('procedure_id')
                        ->label('Procedimiento')
                        ->relationship('procedure', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('tooth')
                        ->label('Pieza')
                        ->maxLength(10),
                    Select::make('work_type')
                        ->label('Tipo de trabajo')
                        ->options([
                            'crown' => 'Corona',
                            'bridge' => 'Puente',
                            'denture' => 'Protesis',
                            'implant_abutment' => 'Pilar de implante',
                            'orthodontic' => 'Ortodoncia',
                            'other' => 'Otro',
                        ])
                        ->required(),
                    TextInput::make('material')
                        ->label('Material')
                        ->maxLength(100),
                    TextInput::make('shade')
                        ->label('Color/ tono')
                        ->maxLength(50),
                    TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),
                    Textarea::make('specifications')
                        ->label('Especificaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->addActionLabel('Agregar item')
                ->defaultItems(1)
                ->collapsible(),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('laboratory_name')
                    ->label('Laboratorio')
                    ->searchable(),
                TextColumn::make('professional.name')
                    ->label('Profesional')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'ordered' => 'Ordenado',
                        'sent' => 'Enviado',
                        'accepted_by_lab' => 'Aceptado',
                        'in_production' => 'En produccion',
                        'received' => 'Recibido',
                        'quality_review' => 'Control calidad',
                        'ready_for_patient' => 'Listo',
                        'adjustment_required' => 'Ajuste',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ordered' => 'warning',
                        'sent' => 'info',
                        'accepted_by_lab' => 'info',
                        'in_production' => 'info',
                        'received' => 'success',
                        'quality_review' => 'warning',
                        'ready_for_patient' => 'success',
                        'adjustment_required' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'primary',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('promised_date')
                    ->label('Prometido')
                    ->date('d/m/Y')
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
            'index' => ListDentalLaboratoryCases::route('/'),
            'create' => CreateDentalLaboratoryCase::route('/create'),
            'edit' => EditDentalLaboratoryCase::route('/{record}/edit'),
        ];
    }
}
