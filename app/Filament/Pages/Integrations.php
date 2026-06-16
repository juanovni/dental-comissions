<?php

namespace App\Filament\Pages;

use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Services\MetaSocialService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Integrations extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Integraciones';

    protected static ?string $title = 'Integraciones';

    protected static ?string $slug = 'integrations';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.integrations';

    public function metaStats(): array
    {
        $accounts = SocialAccount::query()
            ->whereIn('platform', [
                SocialPlatform::Facebook->value,
                SocialPlatform::Instagram->value,
            ])
            ->get();
        $activeAccounts = $accounts->where('is_active', true);

        return [
            'connected' => $activeAccounts->isNotEmpty(),
            'facebook_accounts' => $activeAccounts->where('platform', SocialPlatform::Facebook)->count(),
            'instagram_accounts' => $activeAccounts->where('platform', SocialPlatform::Instagram)->count(),
            'active_accounts' => $activeAccounts->count(),
            'last_synced_at' => $activeAccounts->max('last_synced_at'),
            'accounts_url' => '/admin/social-accounts',
            'connect_url' => route('meta.auth.redirect', [], false),
        ];
    }

    public function syncMeta(): void
    {
        try {
            $summary = app(MetaSocialService::class)->syncAll();

            Notification::make()
                ->title('Meta sincronizado')
                ->body("Cuentas: {$summary['accounts']}. Posts: {$summary['posts']}. Comentarios: {$summary['comments']}. Errores: {$summary['errors']}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo sincronizar Meta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
