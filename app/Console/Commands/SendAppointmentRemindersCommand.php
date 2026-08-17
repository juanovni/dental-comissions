<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsForEachClinic;
use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    use RunsForEachClinic;

    protected $signature = 'appointments:send-reminders {--clinic= : ID de clinica a procesar}';

    protected $description = 'Procesa recordatorios de confirmacion de citas.';

    public function handle(AppointmentReminderService $service): int
    {
        return $this->runForEachClinic(function () use ($service): void {
            $summary = $service->run();

            foreach ($summary as $key => $count) {
                $this->line("{$key}: {$count}");
            }
        });
    }
}
