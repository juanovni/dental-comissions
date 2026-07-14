<?php

namespace App\Filament\Resources\LocalLanguagePatterns\Pages;

use App\Filament\Resources\LocalLanguagePatterns\LocalLanguagePatternResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocalLanguagePattern extends CreateRecord
{
    protected static string $resource = LocalLanguagePatternResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approved_by'] = auth()->id();
        $data['approved_at'] = now();

        return $data;
    }
}
