<?php

namespace App\Filament\Resources\DoctorAssistantAssignments\Pages;

use App\Filament\Resources\DoctorAssistantAssignments\DoctorAssistantAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDoctorAssistantAssignments extends ListRecords
{
    protected static string $resource = DoctorAssistantAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
