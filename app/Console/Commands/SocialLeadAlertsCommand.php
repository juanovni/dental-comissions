<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachClinic;
use App\Services\SocialLeadAlertService;
use Illuminate\Console\Command;

class SocialLeadAlertsCommand extends Command
{
    use RunsForEachClinic;

    protected $signature = 'social:lead-alerts {--clinic= : ID de clinica a procesar}';

    protected $description = 'Genera alertas operativas para leads sociales.';

    public function handle(SocialLeadAlertService $service): int
    {
        return $this->runForEachClinic(function () use ($service): void {
            $summary = $service->runScheduledChecks();

            $this->info('Alertas generadas: '.array_sum($summary));

            foreach ($summary as $type => $count) {
                $this->line("{$type}: {$count}");
            }
        });
    }
}
