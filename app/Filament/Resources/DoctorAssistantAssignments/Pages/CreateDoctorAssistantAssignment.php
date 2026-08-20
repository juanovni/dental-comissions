<?php

namespace App\Filament\Resources\DoctorAssistantAssignments\Pages;

use App\Filament\Resources\DoctorAssistantAssignments\DoctorAssistantAssignmentResource;
use App\Models\DoctorAssistantAssignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDoctorAssistantAssignment extends CreateRecord
{
    protected static string $resource = DoctorAssistantAssignmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (DoctorAssistantAssignment::query()
            ->forCurrentTenant()
            ->where('doctor_id', $data['doctor_id'])
            ->where('assistant_id', $data['assistant_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'data.assistant_id' => 'Este auxiliar ya esta asignado a este doctor.',
            ]);
        }

        return $data;
    }
}
