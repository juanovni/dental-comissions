<?php

namespace App\Filament\Resources\ClinicalCases\Pages;

use App\Filament\Resources\ClinicalCases\ClinicalCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinicalCases extends ListRecords
{
    protected static string $resource = ClinicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
