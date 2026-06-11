<?php

namespace App\Filament\Widgets;

use App\Enums\ProfessionalRole;
use App\Models\ActivityRecord;
use App\Models\Professional;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DoctorCommissionsTable extends TableWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = ['md' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Resumen mensual por doctor')
            ->description('Productividad, pacientes y comision acumulada del mes actual.')
            ->query($this->doctorsQuery())
            ->striped()
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                IconColumn::make('is_active')
                    ->label('')
                    ->boolean(),
                TextColumn::make('name')
                    ->label('Doctor')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('month_activities')
                    ->label('Actividades')
                    ->badge()
                    ->color('info')
                    ->state(fn (Professional $record): int => $this->activitiesQuery($record)->count()),
                TextColumn::make('month_patients')
                    ->label('Pacientes')
                    ->badge()
                    ->color('gray')
                    ->state(fn (Professional $record): int => $this->activitiesQuery($record)->distinct('patient_id')->count('patient_id')),
                TextColumn::make('month_commission')
                    ->label('Comision')
                    ->money('USD')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (Professional $record): float => (float) $this->activitiesQuery($record)->sum('doctor_commission_amount')),
            ]);
    }

    private function doctorsQuery(): Builder
    {
        return Professional::query()
            ->where('role', ProfessionalRole::Doctor->value)
            ->where('is_active', true)
            ->orderBy('name');
    }

    private function activitiesQuery(Professional $doctor): Builder
    {
        return ActivityRecord::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('activity_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]);
    }
}
