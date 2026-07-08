<?php

namespace App\Filament\Resources\SocialCrmSettings\Pages;

use App\Filament\Resources\SocialCrmSettings\SocialCrmSettingResource;
use App\Services\SocialCrmSettingsService;
use Filament\Resources\Pages\CreateRecord;

class CreateSocialCrmSetting extends CreateRecord
{
    protected static string $resource = SocialCrmSettingResource::class;

    protected function afterCreate(): void
    {
        app(SocialCrmSettingsService::class)->clearCache();
    }
}
