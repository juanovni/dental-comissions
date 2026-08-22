<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use Illuminate\Console\Command;

class ClearClinicWhatsappConfigCommand extends Command
{
    protected $signature = 'clinics:clear-whatsapp-config
        {clinic=clinic-1 : ID, slug, subdomain or primary domain of the clinic}
        {--dry-run : Show what would be changed without saving}';

    protected $description = 'Clear WhatsApp Cloud API settings from a clinic tenant';

    public function handle(): int
    {
        $clinic = $this->findClinic((string) $this->argument('clinic'));

        if (! $clinic) {
            $this->error('Clinic not found.');

            return self::FAILURE;
        }

        $settings = $clinic->settings ?? [];
        $before = [
            'phone_number_id' => data_get($settings, 'integrations.whatsapp.phone_number_id'),
            'business_phone' => data_get($settings, 'integrations.whatsapp.business_phone'),
            'access_token_configured' => filled(data_get($settings, 'integrations.whatsapp.access_token')),
            'legacy_phone_number_id' => data_get($settings, 'whatsapp_phone_number_id'),
        ];

        $this->info("Clinic: {$clinic->id} / {$clinic->primary_domain}");
        $this->table(['Campo', 'Valor'], collect($before)->map(fn (mixed $value, string $key): array => [
            $key,
            is_bool($value) ? ($value ? 'yes' : 'no') : ($value ?: '(empty)'),
        ])->values()->all());

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No changes saved.');

            return self::SUCCESS;
        }

        unset($settings['whatsapp_phone_number_id']);

        if (isset($settings['integrations']) && is_array($settings['integrations'])) {
            unset($settings['integrations']['whatsapp']);

            if ($settings['integrations'] === []) {
                unset($settings['integrations']);
            }
        }

        $clinic->update(['settings' => $settings]);

        $this->info('WhatsApp settings cleared.');

        return self::SUCCESS;
    }

    private function findClinic(string $value): ?Clinic
    {
        return Clinic::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('slug', $value)
            ->orWhere('subdomain', $value)
            ->orWhere('primary_domain', $value)
            ->first();
    }
}
