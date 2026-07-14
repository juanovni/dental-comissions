<?php

namespace App\Filament\Resources\LocalLanguagePatterns\Pages;

use App\Filament\Resources\LocalLanguagePatterns\LocalLanguagePatternResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocalLanguagePatterns extends ListRecords
{
    protected static string $resource = LocalLanguagePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
