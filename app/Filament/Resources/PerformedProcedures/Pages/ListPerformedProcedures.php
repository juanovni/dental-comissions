<?php

namespace App\Filament\Resources\PerformedProcedures\Pages;

use App\Filament\Resources\PerformedProcedures\PerformedProcedureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerformedProcedures extends ListRecords
{
    protected static string $resource = PerformedProcedureResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
