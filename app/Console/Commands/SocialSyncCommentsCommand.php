<?php

namespace App\Console\Commands;

use App\Services\MetaSocialService;
use Illuminate\Console\Command;

class SocialSyncCommentsCommand extends Command
{
    protected $signature = 'social:sync-comments';

    protected $description = 'Sincroniza cuentas, publicaciones y comentarios desde Meta Graph API.';

    public function handle(MetaSocialService $metaSocialService): int
    {
        $this->info('Sincronizando comentarios de Instagram/Facebook...');

        $summary = $metaSocialService->syncAll();

        $this->components->info(sprintf(
            'Sincronizacion completada. Cuentas: %d, posts: %d, comentarios: %d, errores: %d.',
            $summary['accounts'],
            $summary['posts'],
            $summary['comments'],
            $summary['errors'],
        ));

        return self::SUCCESS;
    }
}
