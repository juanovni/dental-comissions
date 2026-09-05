<?php

namespace App\Filament\Resources\DentalLaboratoryCases\Pages;

use App\Filament\Resources\DentalLaboratoryCases\DentalLaboratoryCaseResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListDentalLaboratoryCases extends ListRecords
{
    protected static string $resource = DentalLaboratoryCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
