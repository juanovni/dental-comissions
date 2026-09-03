<?php

namespace App\Filament\Resources\PerformedProcedures\Pages;

use App\Filament\Resources\PerformedProcedures\PerformedProcedureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPerformedProcedure extends EditRecord
{
    protected static string $resource = PerformedProcedureResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
