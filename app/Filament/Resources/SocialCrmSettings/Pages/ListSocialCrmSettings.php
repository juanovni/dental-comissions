<?php

namespace App\Filament\Resources\SocialCrmSettings\Pages;

use App\Filament\Resources\SocialCrmSettings\SocialCrmSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSocialCrmSettings extends ListRecords
{
    protected static string $resource = SocialCrmSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
