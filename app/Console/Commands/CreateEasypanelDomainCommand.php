<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Services\EasypanelDomainService;
use Illuminate\Console\Command;

class CreateEasypanelDomainCommand extends Command
{
    protected $signature = 'easypanel:create-domain {clinic : ID, slug, subdomain or primary domain of the clinic}';

    protected $description = 'Create or sync the Easypanel domain for a clinic tenant';

    public function handle(EasypanelDomainService $service): int
    {
        $value = (string) $this->argument('clinic');

        $clinic = Clinic::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('slug', $value)
            ->orWhere('subdomain', $value)
            ->orWhere('primary_domain', $value)
            ->first();

        if (! $clinic) {
            $this->error('Clinic not found.');

            return self::FAILURE;
        }

        $created = $service->ensureDomainForClinic($clinic);

        if (! $created) {
            $this->warn('Easypanel domain was not created. Check laravel.log and clinic settings.');

            return self::FAILURE;
        }

        $this->info('Easypanel domain sync requested successfully.');

        return self::SUCCESS;
    }
}
