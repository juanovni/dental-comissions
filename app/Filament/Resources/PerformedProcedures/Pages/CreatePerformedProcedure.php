<?php

namespace App\Filament\Resources\PerformedProcedures\Pages;

use App\Filament\Resources\PerformedProcedures\PerformedProcedureResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformedProcedure extends CreateRecord
{
    protected static string $resource = PerformedProcedureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
