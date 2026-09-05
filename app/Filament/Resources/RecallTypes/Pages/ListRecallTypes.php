<?php

namespace App\Filament\Resources\RecallTypes\Pages;

use App\Filament\Resources\RecallTypes\RecallTypeResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListRecallTypes extends ListRecords
{
    protected static string $resource = RecallTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
