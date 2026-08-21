<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EasypanelDomainService
{
    public function ensureDomainForClinic(Clinic $clinic): bool
    {
        $domain = $this->domainForClinic($clinic);

        if (! $domain) {
            return false;
        }

        if (! $this->isPublicDomain($domain)) {
            Log::info('Dominio local omitido para Easypanel.', [
                'clinic_id' => $clinic->id,
                'domain' => $domain,
            ]);

            $this->markDomainStatus($clinic, 'skipped', 'Dominio local/no publico');

            return false;
        }

        if (! $this->isConfigured()) {
            Log::warning('Easypanel no configurado. Dominio no creado.', [
                'clinic_id' => $clinic->id,
                'domain' => $domain,
            ]);

            $this->markDomainStatus($clinic, 'skipped', 'Easypanel no configurado');

            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => (string) config('services.easypanel.api_key'),
        ])
            ->acceptJson()
            ->asJson()
            ->post($this->endpoint(), $this->payload($domain));

        if ($response->successful()) {
            $this->markDomainStatus($clinic, 'created');

            return true;
        }

        Log::error('No se pudo crear dominio en Easypanel.', [
            'clinic_id' => $clinic->id,
            'domain' => $domain,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $this->markDomainStatus($clinic, 'failed', 'HTTP '.$response->status());

        return false;
    }

    private function domainForClinic(Clinic $clinic): ?string
    {
        $domain = trim((string) $clinic->primary_domain);

        if ($domain === '') {
            return null;
        }

        return Str::of($domain)
            ->replaceStart('https://', '')
            ->replaceStart('http://', '')
            ->before('/')
            ->lower()
            ->toString();
    }

    private function isConfigured(): bool
    {
        return filled(config('services.easypanel.url'))
            && filled(config('services.easypanel.api_key'))
            && filled(config('services.easypanel.project'))
            && filled(config('services.easypanel.service'));
    }

    private function isPublicDomain(string $domain): bool
    {
        return ! str_ends_with($domain, '.localhost')
            && ! str_ends_with($domain, '.test')
            && ! str_ends_with($domain, '.local')
            && $domain !== 'localhost';
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.easypanel.url'), '/')
            .'/'.ltrim((string) config('services.easypanel.domain_endpoint'), '/');
    }

    private function payload(string $domain): array
    {
        return [
            'json' => [
                'certificateResolver' => '',
                'destinationType' => 'service',
                'host' => $domain,
                'https' => true,
                'id' => '',
                'middlewares' => [],
                'path' => '/',
                'serviceDestination' => [
                    'path' => config('services.easypanel.destination_path', '/'),
                    'port' => config('services.easypanel.destination_port', 80),
                    'projectName' => config('services.easypanel.project'),
                    'protocol' => Str::lower((string) config('services.easypanel.destination_protocol', 'http')),
                    'serviceName' => config('services.easypanel.service'),
                ],
                'wildcard' => false,
            ],
        ];
    }

    private function markDomainStatus(Clinic $clinic, string $status, ?string $error = null): void
    {
        $settings = $clinic->settings ?? [];
        $settings['deployment']['easypanel_domain'] = [
            'status' => $status,
            'error' => $error,
            'synced_at' => now()->toIso8601String(),
        ];

        $clinic->forceFill(['settings' => $settings])->saveQuietly();
    }
}
