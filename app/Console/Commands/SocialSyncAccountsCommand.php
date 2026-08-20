<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachClinic;
use App\Services\MetaSocialService;
use Illuminate\Console\Command;

class SocialSyncAccountsCommand extends Command
{
    use RunsForEachClinic;

    protected $signature = 'social:sync-accounts {--clinic= : ID de clinica a procesar}';

    protected $description = 'Sincroniza cuentas autorizadas desde Meta Graph API usando META_ACCESS_TOKEN.';

    public function handle(MetaSocialService $metaSocialService): int
    {
        return $this->runForEachClinic(function () use ($metaSocialService): void {
            $this->info('Sincronizando cuentas autorizadas de Meta...');

            $metaSocialService->syncAuthorizedAccounts();

            $this->components->info('Cuentas autorizadas sincronizadas.');
        });
    }
}
