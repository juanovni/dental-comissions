<?php

namespace App\Filament\Resources\ActivityRecords;

use App\Enums\ActivityStatus;
use App\Enums\ProfessionalRole;
use App\Filament\Resources\ActivityRecords\Pages\CreateActivityRecord;
use App\Filament\Resources\ActivityRecords\Pages\EditActivityRecord;
use App\Filament\Resources\ActivityRecords\Pages\ListActivityRecords;
use App\Models\ActivityRecord;
use App\Models\DoctorAssistantAssignment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityRecordResource extends Resource
{
    protected static ?string $model = ActivityRecord::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Actividades';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'actividades';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('patient_id')
                ->label('Paciente')
                ->options(fn () => Patient::query()->orderBy('full_name')->pluck('full_name', 'id'))
                ->searchable()
                ->required()
                ->createOptionForm([
                    TextInput::make('full_name')->label('Nombre completo')->required()->maxLength(255),
                    TextInput::make('phone')->label('Telefono')->tel()->maxLength(50),
                    DatePicker::make('date_of_birth')->label('Fecha de nacimiento'),
                    Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ])
                ->createOptionUsing(function (array $data): int {
                    $data['normalized_name'] = \Illuminate\Support\Str::of($data['full_name'])->lower()->ascii()->squish();
                    return Patient::create($data)->id;
                }),
            Select::make('doctor_id')
                ->label('Doctor')
                ->options(fn () => Professional::query()
                    ->where('role', ProfessionalRole::Doctor->value)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->live(),
            Select::make('procedure_id')
                ->label('Procedimiento')
                ->options(fn () => Procedure::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn ($p) => [
                        $p->id => "{$p->name}" . ($p->code ? " ({$p->code})" : '') . ($p->internal_rate ? " - $" . number_format($p->internal_rate, 2) : ''),
                    ]))
                ->searchable()
                ->required(),
            DatePicker::make('activity_date')
                ->label('Fecha del procedimiento')
                ->required()
                ->default(now()),
            TimePicker::make('activity_time')
                ->label('Hora')
                ->seconds(false),
            Select::make('assistants')
                ->label('Auxiliares')
                ->multiple()
                ->options(function (callable $get) {
                    $doctorId = $get('doctor_id');
                    if (!$doctorId) {
                        return Professional::query()
                            ->where('role', ProfessionalRole::Assistant->value)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    }
                    $assignedIds = DoctorAssistantAssignment::where('doctor_id', $doctorId)
                        ->where('is_active', true)
                        ->pluck('assistant_id');
                    return Professional::query()
                        ->where('role', ProfessionalRole::Assistant->value)
                        ->where('is_active', true)
                        ->whereIn('id', $assignedIds)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->helperText('Solo auxiliares asignados al doctor seleccionado'),
            Select::make('status')
                ->label('Estado')
                ->options(collect(ActivityStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                ->required()
                ->default(ActivityStatus::PendingConfirmation->value),
            Textarea::make('correction_notes')
                ->label('Notas de correccion')
                ->columnSpanFull()
                ->visible(fn ($record) => $record && $record->status === ActivityStatus::NeedsReview),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')->label('Paciente')->searchable()->sortable(),
                TextColumn::make('doctor.name')->label('Doctor')->searchable()->sortable(),
                TextColumn::make('procedure.name')->label('Procedimiento')->searchable()->sortable(),
                TextColumn::make('activity_date')->label('Fecha')->date()->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (ActivityStatus $state): string => $state->label())
                    ->color(fn (ActivityStatus $state): string => $state->color()),
                TextColumn::make('doctor_commission_amount')->label('Comision doctor')->money('USD'),
                TextColumn::make('assistant_commission_total')->label('Comision auxiliares')->money('USD'),
                TextColumn::make('created_at')->label('Creado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(ActivityStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->options(fn () => Professional::query()
                        ->where('role', ProfessionalRole::Doctor->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')),
                Filter::make('activity_date')
                    ->label('Fecha del procedimiento')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('activity_date', '<=', $date))),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ActivityRecord $record): bool => in_array($record->status, [
                        ActivityStatus::PendingConfirmation,
                        ActivityStatus::NeedsReview,
                    ], true))
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar actividad')
                    ->modalDescription('Esta accion marcara la actividad como aprobada.')
                    ->action(function (ActivityRecord $record): void {
                        $record->approve();
                        Notification::make()
                            ->title('Actividad aprobada')
                            ->success()
                            ->send();
                    }),
                Action::make('request_correction')
                    ->label('Solicitar correccion')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (ActivityRecord $record): bool => in_array($record->status, [
                        ActivityStatus::PendingConfirmation,
                        ActivityStatus::Approved,
                    ], true))
                    ->form([
                        Textarea::make('correction_notes')
                            ->label('Motivo de la correccion')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (ActivityRecord $record, array $data): void {
                        $record->requestCorrection($data['correction_notes']);
                        Notification::make()
                            ->title('Solicitud de correccion registrada')
                            ->warning()
                            ->send();
                    }),
                Action::make('mark_as_paid')
                    ->label('Marcar como pagado')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn (ActivityRecord $record): bool => $record->status === ActivityStatus::Approved)
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como pagado')
                    ->modalDescription('Esta accion marcara la actividad como pagada.')
                    ->action(function (ActivityRecord $record): void {
                        $record->markAsPaid();
                        Notification::make()
                            ->title('Actividad marcada como pagada')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityRecords::route('/'),
            'create' => CreateActivityRecord::route('/create'),
            'edit' => EditActivityRecord::route('/{record}/edit'),
        ];
    }
}
