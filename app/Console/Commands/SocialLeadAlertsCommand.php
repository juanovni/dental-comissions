<?php

namespace App\Console\Commands;

use App\Services\SocialLeadAlertService;
use Illuminate\Console\Command;

class SocialLeadAlertsCommand extends Command
{
    protected $signature = 'social:lead-alerts';

    protected $description = 'Genera alertas operativas para leads sociales.';

    public function handle(SocialLeadAlertService $service): int
    {
        $summary = $service->runScheduledChecks();

        $this->info('Alertas generadas: '.array_sum($summary));

        foreach ($summary as $type => $count) {
            $this->line("{$type}: {$count}");
        }

        return self::SUCCESS;
    }
}
