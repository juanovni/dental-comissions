<?php

namespace App\Filament\Resources\InformedConsentTemplates\Pages;

use App\Filament\Resources\InformedConsentTemplates\InformedConsentTemplateResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListInformedConsentTemplates extends ListRecords
{
    protected static string $resource = InformedConsentTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
