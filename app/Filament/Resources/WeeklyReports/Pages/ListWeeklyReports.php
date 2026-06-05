<?php

namespace App\Filament\Resources\WeeklyReports\Pages;

use App\Filament\Resources\WeeklyReports\WeeklyReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyReports extends ListRecords
{
    protected static string $resource = WeeklyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
