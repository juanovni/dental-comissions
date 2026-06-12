<?php

namespace App\Console\Commands;

use App\Services\MetaSocialService;
use Illuminate\Console\Command;

class SocialSyncAccountsCommand extends Command
{
    protected $signature = 'social:sync-accounts';

    protected $description = 'Sincroniza cuentas autorizadas desde Meta Graph API usando META_ACCESS_TOKEN.';

    public function handle(MetaSocialService $metaSocialService): int
    {
        $this->info('Sincronizando cuentas autorizadas de Meta...');

        $metaSocialService->syncAuthorizedAccounts();

        $this->components->info('Cuentas autorizadas sincronizadas.');

        return self::SUCCESS;
    }
}
