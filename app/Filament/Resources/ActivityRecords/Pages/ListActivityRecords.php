<?php

namespace App\Filament\Resources\ActivityRecords\Pages;

use App\Filament\Resources\ActivityRecords\ActivityRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityRecords extends ListRecords
{
    protected static string $resource = ActivityRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
