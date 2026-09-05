<?php

namespace App\Filament\Resources\ExternalConsultations\Pages;

use App\Filament\Resources\ExternalConsultations\ExternalConsultationResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListExternalConsultations extends ListRecords
{
    protected static string $resource = ExternalConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
