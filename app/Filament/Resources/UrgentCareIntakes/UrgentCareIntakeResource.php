<?php

namespace App\Filament\Resources\UrgentCareIntakes;

use App\Filament\Resources\UrgentCareIntakes\Pages\CreateUrgentCareIntake;
use App\Filament\Resources\UrgentCareIntakes\Pages\EditUrgentCareIntake;
use App\Filament\Resources\UrgentCareIntakes\Pages\ListUrgentCareIntakes;
use App\Models\UrgentCareIntake;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UrgentCareIntakeResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('urgent_care.view') ?? false;
    }

    protected static ?string $model = UrgentCareIntake::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Atencion urgente';

    protected static ?string $navigationLabel = 'Atencion urgente';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'ingreso urgente';

    protected static ?string $pluralModelLabel = 'ingresos urgentes';

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
                ->label('Profesional')
                ->relationship('professional', 'name')
                ->searchable()
                ->preload(),
            Select::make('arrival_mode')
                ->label('Modo de llegada')
                ->options([
                    'walk_in' => 'Sin cita',
                    'ambulance' => 'Ambulancia',
                    'transfer' => 'Traslado',
                    'self_referral' => 'Auto-referido',
                ])
                ->default('walk_in')
                ->required(),
            Select::make('triage_category')
                ->label('Categoria de triage')
                ->options([
                    'immediate' => 'Inmediato',
                    'urgent' => 'Urgente',
                    'semi_urgent' => 'Semi-urgente',
                    'non_urgent' => 'No urgente',
                ])
                ->default('urgent')
                ->required(),
            Textarea::make('chief_complaint')
                ->label('Motivo de consulta')
                ->rows(3)
                ->required()
                ->columnSpanFull(),
            Textarea::make('red_flags')
                ->label('Banderas rojas')
                ->rows(2)
                ->columnSpanFull(),
            Select::make('acute_medical_screen_status')
                ->label('Tamizaje medico agudo')
                ->options([
                    'pending' => 'Pendiente',
                    'cleared' => 'Despejado',
                    'requires_followup' => 'Requiere seguimiento',
                ])
                ->default('pending'),
            Select::make('disposition')
                ->label('Disposition')
                ->options([
                    'discharged' => 'Dado de alta',
                    'admitted' => 'Internado',
                    'transferred' => 'Trasladado',
                    'deferred' => 'Diferido',
                ]),
            Textarea::make('disposition_notes')
                ->label('Notas de disposition')
                ->rows(2)
                ->columnSpanFull(),
            DatePicker::make('deferred_completion_due_at')
                ->label('Fecha limite de completar diferido'),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('arrival_mode')
                    ->label('Llegada')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'walk_in' => 'Sin cita',
                        'ambulance' => 'Ambulancia',
                        'transfer' => 'Traslado',
                        'self_referral' => 'Auto-referido',
                        default => $state,
                    }),
                TextColumn::make('triage_category')
                    ->label('Triage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'immediate' => 'Inmediato',
                        'urgent' => 'Urgente',
                        'semi_urgent' => 'Semi-urgente',
                        'non_urgent' => 'No urgente',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'immediate' => 'danger',
                        'urgent' => 'warning',
                        'semi_urgent' => 'info',
                        'non_urgent' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('professional.name')
                    ->label('Profesional')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('disposition')
                    ->label('Disposition')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'discharged' => 'Dado de alta',
                        'admitted' => 'Internado',
                        'transferred' => 'Trasladado',
                        'deferred' => 'Diferido',
                        default => 'Sin disposition',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'discharged' => 'success',
                        'admitted' => 'info',
                        'transferred' => 'warning',
                        'deferred' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
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
            'index' => ListUrgentCareIntakes::route('/'),
            'create' => CreateUrgentCareIntake::route('/create'),
            'edit' => EditUrgentCareIntake::route('/{record}/edit'),
        ];
    }
}
