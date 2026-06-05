<?php

namespace App\Filament\Resources\WeeklyReports\Pages;

use App\Enums\ProfessionalRole;
use App\Filament\Resources\WeeklyReports\WeeklyReportResource;
use App\Models\Professional;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWeeklyReport extends CreateRecord
{
    protected static string $resource = WeeklyReportResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $weekStart = Carbon::parse($data['week_start']);
        $weekEnd = Carbon::parse($data['week_end']);

        $doctor = Professional::findOrFail($data['professional_id']);

        $report = WeeklyReport::generateForDoctor($doctor, $weekStart, $weekEnd);

        if (!$report) {
            Notification::make()
                ->title('No se pudo generar el reporte')
                ->body('Ya existe un reporte para este doctor en esta semana, o no hay actividades aprobadas en el rango seleccionado.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $report;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Reporte generado')
            ->success()
            ->send();
    }
}
