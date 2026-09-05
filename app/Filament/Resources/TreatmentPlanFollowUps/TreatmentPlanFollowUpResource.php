<?php

namespace App\Filament\Resources\TreatmentPlanFollowUps;

use App\Filament\Resources\TreatmentPlanFollowUps\Pages\CreateTreatmentPlanFollowUp;
use App\Filament\Resources\TreatmentPlanFollowUps\Pages\EditTreatmentPlanFollowUp;
use App\Filament\Resources\TreatmentPlanFollowUps\Pages\ListTreatmentPlanFollowUps;
use App\Models\TreatmentPlanFollowUp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Resource\Concerns\BelongsToTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreatmentPlanFollowUpResource extends Resource
{
    use BelongsToTenant;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRolePermission('follow_ups.view') ?? false;
    }

    protected static ?string $model = TreatmentPlanFollowUp::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Recall clinico y seguimiento';

    protected static ?string $navigationLabel = 'Seguimientos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'seguimiento';

    protected static ?string $pluralModelLabel = 'seguimientos';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('treatment_plan_id')
                ->label('Plan de tratamiento')
                ->relationship('treatmentPlan', 'code')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('treatment_plan_item_id')
                ->label('Item del plan')
                ->relationship('treatmentPlanItem', 'procedure_name_snapshot')
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('channel')
                ->label('Canal')
                ->options([
                    'whatsapp' => 'WhatsApp',
                    'phone' => 'Telefono',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'in_person' => 'En persona',
                ])
                ->required(),
            TextInput::make('reason')
                ->label('Motivo')
                ->required()
                ->maxLength(255),
            Select::make('status')
                ->label('Estado')
                ->options([
                    'pending' => 'Pendiente',
                    'performed' => 'Realizado',
                    'failed' => 'Fallido',
                    'cancelled' => 'Cancelado',
                ])
                ->default('pending')
                ->required(),
            DateTimePicker::make('scheduled_at')
                ->label('Programado para')
                ->required(),
            DateTimePicker::make('performed_at')
                ->label('Realizado el'),
            Select::make('outcome')
                ->label('Resultado')
                ->options([
                    'contacted' => 'Contactado',
                    'no_answer' => 'Sin respuesta',
                    'rescheduled' => 'Reprogramado',
                    'completed' => 'Completado',
                    'declined' => 'Rechazado',
                ]),
            Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
            DateTimePicker::make('next_action_at')
                ->label('Proxima accion'),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('treatmentPlan.code')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whatsapp' => 'WhatsApp',
                        'phone' => 'Telefono',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'in_person' => 'En persona',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'performed' => 'Realizado',
                        'failed' => 'Fallido',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'performed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('outcome')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'contacted' => 'Contactado',
                        'no_answer' => 'Sin respuesta',
                        'rescheduled' => 'Reprogramado',
                        'completed' => 'Completado',
                        'declined' => 'Rechazado',
                        default => 'Sin resultado',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Programado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('next_action_at')
                    ->label('Proxima accion')
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
            'index' => ListTreatmentPlanFollowUps::route('/'),
            'create' => CreateTreatmentPlanFollowUp::route('/create'),
            'edit' => EditTreatmentPlanFollowUp::route('/{record}/edit'),
        ];
    }
}
