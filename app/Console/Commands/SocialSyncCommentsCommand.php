<?php

namespace App\Console\Commands;

use App\Services\MetaSocialService;
use Illuminate\Console\Command;

class SocialSyncCommentsCommand extends Command
{
    protected $signature = 'social:sync-comments {--refresh-accounts : Actualiza cuentas autorizadas desde Meta antes de sincronizar posts y comentarios}';

    protected $description = 'Sincroniza publicaciones y comentarios de cuentas sociales ya conectadas.';

    public function handle(MetaSocialService $metaSocialService): int
    {
        $this->info('Sincronizando comentarios de Instagram/Facebook...');

        $summary = $metaSocialService->syncAll((bool) $this->option('refresh-accounts'));

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
