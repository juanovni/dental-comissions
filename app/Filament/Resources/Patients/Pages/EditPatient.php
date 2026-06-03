<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['normalized_name'] = Str::of($data['normalized_name'] ?? $data['full_name'])->lower()->ascii()->squish()->toString();

        return $data;
    }
}
