<?php

namespace App\Filament\Resources\Appointments;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Citas CRM';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'cita CRM';

    protected static ?string $pluralModelLabel = 'citas CRM';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'doctor', 'procedure', 'socialComment', 'socialComment.socialIdentity']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('patient_id')
                ->label('Paciente')
                ->options(fn (): array => Patient::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                ->searchable()
                ->nullable(),
            Select::make('doctor_id')
                ->label('Doctor')
                ->options(fn (): array => Professional::query()->where('role', 'doctor')->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->nullable(),
            Select::make('procedure_id')
                ->label('Procedimiento')
                ->options(fn (): array => Procedure::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->nullable(),
            DateTimePicker::make('scheduled_at')
                ->label('Fecha y hora')
                ->required(),
            TextInput::make('duration_minutes')
                ->label('Duracion (min)')
                ->numeric()
                ->minValue(1)
                ->default(45),
            Select::make('status')
                ->label('Estado')
                ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn (AppointmentStatus $s): array => [$s->value => $s->label()]))
                ->required(),
            Select::make('source')
                ->label('Origen')
                ->options(collect(AppointmentSource::cases())->mapWithKeys(fn (AppointmentSource $s): array => [$s->value => $s->label()]))
                ->required(),
            Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('procedure.name')
                    ->label('Procedimiento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Agendada')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duracion')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (AppointmentStatus $state): string => $state->label())
                    ->color(fn (AppointmentStatus $state): string => $state->color()),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (AppointmentSource $state): string => $state->label()),
                TextColumn::make('socialComment.socialIdentity.display_name')
                    ->label('Lead')
                    ->placeholder('-'),
                IconColumn::make('is_synced')
                    ->label('Sync')
                    ->state(fn (Appointment $record): bool => $record->isSynced())
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn (AppointmentStatus $s): array => [$s->value => $s->label()])),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options(collect(AppointmentSource::cases())->mapWithKeys(fn (AppointmentSource $s): array => [$s->value => $s->label()])),
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->options(fn (): array => Professional::query()->where('role', 'doctor')->orderBy('name')->pluck('name', 'id')->all()),
                Filter::make('scheduled_at')
                    ->label('Rango de fecha')
                    ->form([
                        DateTimePicker::make('from')->label('Desde'),
                        DateTimePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $from): Builder => $q->where('scheduled_at', '>=', Carbon::parse($from)))
                        ->when($data['until'] ?? null, fn (Builder $q, $until): Builder => $q->where('scheduled_at', '<=', Carbon::parse($until)))),
                Filter::make('needs_sync')
                    ->label('Sin sync')
                    ->query(fn (Builder $query): Builder => $query->where('external_appointment_id', null)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ...self::appointmentActions(),
            ]);
    }

    public static function appointmentActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirmar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar cita')
                ->modalDescription('Se confirmara la cita y se sincronizara con Google Calendar si aplica.')
                ->visible(fn (Appointment $record): bool => in_array($record->status, [
                    AppointmentStatus::PendingConfirmation,
                    AppointmentStatus::Scheduled,
                ], true))
                ->action(function (Appointment $record): void {
                    try {
                        app(\App\Services\AppointmentWorkflowService::class)->confirm($record);
                        Notification::make()->title('Cita confirmada y sincronizada')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error al confirmar')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('reschedule')
                ->label('Reprogramar')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->modalHeading('Reprogramar cita')
                ->modalSubmitActionLabel('Reprogramar')
                ->form([
                    DateTimePicker::make('scheduled_at')
                        ->label('Nueva fecha y hora')
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->label('Duracion (min)')
                        ->numeric()
                        ->minValue(1),
                ])
                ->visible(fn (Appointment $record): bool => in_array($record->status, [
                    AppointmentStatus::PendingConfirmation,
                    AppointmentStatus::Scheduled,
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::Rescheduled,
                ], true))
                ->action(function (Appointment $record, array $data): void {
                    try {
                        app(\App\Services\AppointmentWorkflowService::class)->reschedule(
                            $record,
                            Carbon::parse($data['scheduled_at']),
                            (int) ($data['duration_minutes'] ?? $record->duration_minutes),
                        );
                        Notification::make()->title('Cita reprogramada y sincronizada')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error al reprogramar')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar cita')
                ->modalDescription('La cita se cancelara y se eliminara del calendario si aplica.')
                ->form([
                    Textarea::make('reason')
                        ->label('Motivo de cancelacion')
                        ->rows(3),
                ])
                ->visible(fn (Appointment $record): bool => in_array($record->status, [
                    AppointmentStatus::PendingConfirmation,
                    AppointmentStatus::Scheduled,
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::Rescheduled,
                ], true))
                ->action(function (Appointment $record, array $data): void {
                    try {
                        app(\App\Services\AppointmentWorkflowService::class)->cancel(
                            $record,
                            $data['reason'] ?? null,
                        );
                        Notification::make()->title('Cita cancelada')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error al cancelar')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('complete')
                ->label('Completar')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Marcar como completada')
                ->visible(fn (Appointment $record): bool => in_array($record->status, [
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::Scheduled,
                    AppointmentStatus::Rescheduled,
                ], true))
                ->action(function (Appointment $record): void {
                    try {
                        app(\App\Services\AppointmentWorkflowService::class)->complete($record);
                        Notification::make()->title('Cita marcada como completada')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('no_show')
                ->label('No asistio')
                ->icon('heroicon-o-user-minus')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Marcar como no asistio')
                ->modalDescription('Se eliminara del calendario si aplica.')
                ->visible(fn (Appointment $record): bool => in_array($record->status, [
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::Scheduled,
                    AppointmentStatus::Rescheduled,
                ], true))
                ->action(function (Appointment $record): void {
                    try {
                        app(\App\Services\AppointmentWorkflowService::class)->markNoShow($record);
                        Notification::make()->title('Marcada como no asistio')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('sync_to_calendar')
                ->label('Sincronizar')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->visible(fn (Appointment $record): bool => $record->doctor_id
                    && $record->doctor?->hasGoogleCalendar()
                    && !$record->isSynced())
                ->action(function (Appointment $record): void {
                    try {
                        $eventId = app(\App\Services\AppointmentWorkflowService::class)->syncToCalendar($record);

                        if ($eventId) {
                            Notification::make()->title('Sincronizada con Google Calendar')->success()->send();
                        } else {
                            Notification::make()
                                ->title('No se pudo sincronizar')
                                ->body('Verifica que el doctor tenga conexion con Google Calendar.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error de sincronizacion')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'edit' => EditAppointment::route('/{record}/edit'),
            'view' => ViewAppointment::route('/{record}'),
        ];
    }
}
