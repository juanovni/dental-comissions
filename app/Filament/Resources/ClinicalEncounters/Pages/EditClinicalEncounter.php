<?php

namespace App\Filament\Resources\ClinicalEncounters\Pages;

use App\Filament\Resources\ClinicalEncounters\ClinicalEncounterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClinicalEncounter extends EditRecord
{
    protected static string $resource = ClinicalEncounterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
