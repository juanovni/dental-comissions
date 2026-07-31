<?php

namespace App\Console\Commands;

use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Procesa recordatorios de confirmacion de citas.';

    public function handle(AppointmentReminderService $service): int
    {
        $summary = $service->run();

        foreach ($summary as $key => $count) {
            $this->line("{$key}: {$count}");
        }

        return self::SUCCESS;
    }
}
