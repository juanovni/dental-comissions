<?php

namespace App\Filament\Resources\TreatmentPlanFollowUps\Pages;

use App\Filament\Resources\TreatmentPlanFollowUps\TreatmentPlanFollowUpResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ListRecords;

class ListTreatmentPlanFollowUps extends ListRecords
{
    protected static string $resource = TreatmentPlanFollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
