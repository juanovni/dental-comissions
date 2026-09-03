<?php

namespace App\Filament\Resources\ClinicalCases\Pages;

use App\Filament\Resources\ClinicalCases\ClinicalCaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClinicalCase extends CreateRecord
{
    protected static string $resource = ClinicalCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = Filament::getTenant()?->getKey();
        $data['created_by'] = auth()->id();

        return $data;
    }
}
