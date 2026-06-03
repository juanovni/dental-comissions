<?php

namespace App\Filament\Resources\DoctorAssistantAssignments\Pages;

use App\Filament\Resources\DoctorAssistantAssignments\DoctorAssistantAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDoctorAssistantAssignment extends EditRecord
{
    protected static string $resource = DoctorAssistantAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
