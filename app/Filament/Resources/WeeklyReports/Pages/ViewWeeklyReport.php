<?php

namespace App\Filament\Resources\WeeklyReports\Pages;

use App\Enums\WeeklyReportStatus;
use App\Filament\Resources\WeeklyReports\WeeklyReportResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewWeeklyReport extends ViewRecord
{
    protected static string $resource = WeeklyReportResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('professional.name')->label('Doctor'),
            TextEntry::make('week_label')->label('Semana'),
            TextEntry::make('status')
                ->label('Estado')
                ->badge()
                ->formatStateUsing(fn ($state) => $state->label())
                ->color(fn ($state) => $state->color()),
            TextEntry::make('total_activities')->label('Actividades'),
            TextEntry::make('total_patients')->label('Pacientes'),
            TextEntry::make('total_procedures')->label('Procedimientos'),
            TextEntry::make('total_doctor_commission')->label('Comision doctor')->money('USD'),
            TextEntry::make('total_commission')->label('Total comisiones')->money('USD'),
            TextEntry::make('notes')->label('Notas')->columnSpanFull(),
            TextEntry::make('approved_at')->label('Aprobado el')->dateTime(),
            TextEntry::make('paid_at')->label('Pagado el')->dateTime(),
        ])->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function ($record) {
                    $record->load('activities.patient', 'activities.procedure', 'activities.doctor', 'activities.paymentMethod');

                    $pdf = Pdf::loadView('weekly-report-pdf', ['report' => $record]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        "reporte-semanal-{$record->professional->name}-{$record->week_start->format('Y-m-d')}.pdf"
                    );
                }),
        ];
    }
}
