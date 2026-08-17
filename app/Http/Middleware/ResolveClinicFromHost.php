<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveClinicFromHost
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clinic = $this->resolveClinicFromHost($request->getHost());

        if ($clinic !== null) {
            $this->tenantContext->set($clinic);
        }

        return $next($request);
    }

    public function resolveClinicFromHost(string $host): ?Clinic
    {
        if ($host === '' || $host === config('tenancy.admin_domain')) {
            return null;
        }

        $query = Clinic::query()->where('primary_domain', $host);

        $prefix = $this->extractSubdomain($host);

        if ($prefix !== null) {
            $query->orWhere('subdomain', $prefix)->orWhere('slug', $prefix);
        }

        return $query->first();
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
