<?php

namespace App\Filament\Resources\LocalLanguagePatterns\Pages;

use App\Filament\Resources\LocalLanguagePatterns\LocalLanguagePatternResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocalLanguagePattern extends EditRecord
{
    protected static string $resource = LocalLanguagePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
