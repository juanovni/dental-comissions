<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['normalized_name'] = Str::of($data['normalized_name'] ?? $data['full_name'])->lower()->ascii()->squish()->toString();

        return $data;
    }
}
