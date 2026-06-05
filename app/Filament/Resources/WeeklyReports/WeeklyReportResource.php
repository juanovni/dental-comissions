<?php

namespace App\Filament\Resources\WeeklyReports;

use App\Enums\ProfessionalRole;
use App\Enums\WeeklyReportStatus;
use App\Filament\Resources\WeeklyReports\Pages\CreateWeeklyReport;
use App\Filament\Resources\WeeklyReports\Pages\EditWeeklyReport;
use App\Filament\Resources\WeeklyReports\Pages\ListWeeklyReports;
use App\Filament\Resources\WeeklyReports\Pages\ViewWeeklyReport;
use App\Models\Professional;
use App\Models\WeeklyReport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WeeklyReportResource extends Resource
{
    protected static ?string $model = WeeklyReport::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reportes semanales';

    protected static ?string $modelLabel = 'reporte semanal';

    protected static ?string $pluralModelLabel = 'reportes semanales';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('professional_id')
                ->label('Doctor')
                ->options(fn () => Professional::query()
                    ->where('role', ProfessionalRole::Doctor->value)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required(),
            DatePicker::make('week_start')
                ->label('Inicio de semana')
                ->required()
                ->live()
                ->afterStateUpdated(fn ($set, $state) => $set(
                    'week_end',
                    $state ? \Carbon\Carbon::parse($state)->addDays(6)->toDateString() : null,
                )),
            DatePicker::make('week_end')
                ->label('Fin de semana')
                ->required()
                ->readOnly(),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('professional.name')->label('Doctor')->searchable()->sortable(),
                TextColumn::make('week_start')
                    ->label('Semana')
                    ->formatStateUsing(fn ($state, $record): string => $record->week_label)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (WeeklyReportStatus $state): string => $state->label())
                    ->color(fn (WeeklyReportStatus $state): string => $state->color()),
                TextColumn::make('total_activities')->label('Actividades')->sortable(),
                TextColumn::make('total_patients')->label('Pacientes')->sortable(),
                TextColumn::make('total_doctor_commission')->label('Com. doctor')->money('USD'),
                TextColumn::make('total_assistant_commission')->label('Com. auxiliares')->money('USD'),
                TextColumn::make('total_commission')->label('Total')->money('USD')->weight('bold'),
                TextColumn::make('created_at')->label('Creado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(WeeklyReportStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('professional_id')
                    ->label('Doctor')
                    ->options(fn () => Professional::query()
                        ->where('role', ProfessionalRole::Doctor->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')),
                Filter::make('week_start')
                    ->label('Semana')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('week_start', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('week_start', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeeklyReports::route('/'),
            'create' => CreateWeeklyReport::route('/create'),
            'edit' => EditWeeklyReport::route('/{record}/edit'),
            'view' => ViewWeeklyReport::route('/{record}'),
        ];
    }
}
