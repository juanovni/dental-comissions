<?php

namespace App\Filament\Resources\ClinicalEncounters\Pages;

use App\Filament\Resources\ClinicalEncounters\ClinicalEncounterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClinicalEncounter extends CreateRecord
{
    protected static string $resource = ClinicalEncounterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
