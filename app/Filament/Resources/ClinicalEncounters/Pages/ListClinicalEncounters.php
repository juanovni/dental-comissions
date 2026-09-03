<?php

namespace App\Filament\Resources\ClinicalEncounters\Pages;

use App\Filament\Resources\ClinicalEncounters\ClinicalEncounterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinicalEncounters extends ListRecords
{
    protected static string $resource = ClinicalEncounterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
