<?php

namespace App\Filament\Resources\SocialCrmSettings\Pages;

use App\Filament\Resources\SocialCrmSettings\SocialCrmSettingResource;
use App\Services\SocialCrmSettingsService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSocialCrmSetting extends EditRecord
{
    protected static string $resource = SocialCrmSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(SocialCrmSettingsService::class)->clearCache();
    }

    protected function afterDelete(): void
    {
        app(SocialCrmSettingsService::class)->clearCache();
    }
}
