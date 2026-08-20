<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveClinicFromRequest
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clinic = $this->resolveClinic($request);

        if ($clinic !== null) {
            $this->tenantContext->set($clinic);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function resolveClinic(Request $request): ?Clinic
    {
        $host = $request->getHost();

        if ($host !== '' && $host !== config('tenancy.admin_domain')) {
            $query = Clinic::query()->where('primary_domain', $host);

            $prefix = $this->extractSubdomain($host);

            if ($prefix !== null) {
                $query->orWhere('subdomain', $prefix)->orWhere('slug', $prefix);
            }

            $clinic = $query->first();

            if ($clinic !== null) {
                return $clinic;
            }
        }

        $clinicSlug = $request->route('clinicSlug');

        if (is_string($clinicSlug) && $clinicSlug !== '') {
            return Clinic::query()->where('slug', $clinicSlug)->first();
        }

        return null;
    }

    private function extractSubdomain(string $host): ?string
    {
        $baseDomain = config('tenancy.base_domain');

        if (! is_string($baseDomain) || $baseDomain === '') {
            return null;
        }

        $suffix = '.'.$baseDomain;

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        return $subdomain !== '' ? $subdomain : null;
    }
}
