<?php

namespace App\Filament\Resources\ClinicalCases\Pages;

use App\Filament\Resources\ClinicalCases\ClinicalCaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClinicalCase extends EditRecord
{
    protected static string $resource = ClinicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
