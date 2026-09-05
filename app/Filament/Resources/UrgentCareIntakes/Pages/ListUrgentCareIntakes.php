<?php

namespace App\Filament\Resources\UrgentCareIntakes\Pages;

use App\Filament\Resources\UrgentCareIntakes\UrgentCareIntakeResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListUrgentCareIntakes extends ListRecords
{
    protected static string $resource = UrgentCareIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
