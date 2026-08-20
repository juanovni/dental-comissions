<?php

namespace App\Filament\Resources\DoctorAssistantAssignments\Pages;

use App\Filament\Resources\DoctorAssistantAssignments\DoctorAssistantAssignmentResource;
use App\Models\DoctorAssistantAssignment;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDoctorAssistantAssignment extends EditRecord
{
    protected static string $resource = DoctorAssistantAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (DoctorAssistantAssignment::query()
            ->forCurrentTenant()
            ->where('doctor_id', $data['doctor_id'])
            ->where('assistant_id', $data['assistant_id'])
            ->whereKeyNot($this->record->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'data.assistant_id' => 'Este auxiliar ya esta asignado a este doctor.',
            ]);
        }

        return $data;
    }
}
