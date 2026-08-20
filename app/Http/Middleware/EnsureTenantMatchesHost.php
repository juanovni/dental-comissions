<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Support\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMatchesHost
{
    public function __construct(
        private TenantContext $tenantContext,
        private ResolveClinicFromHost $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clinicFromHost = $this->tenantContext->get();

        if ($clinicFromHost === null) {
            if (Filament::getCurrentPanel()?->getId() === 'clinic') {
                $clinicFromHost = $this->resolver->resolveClinicFromHost($request->getHost());

                if ($clinicFromHost === null) {
                    abort(404);
                }

                $this->tenantContext->set($clinicFromHost);
            } else {
                return $next($request);
            }
        }

        $routeClinic = $this->resolveRouteClinic($request);

        if (($routeClinic !== null) && $routeClinic->isNot($clinicFromHost)) {
            abort(404);
        }

        if (Filament::getCurrentPanel()?->getId() === 'clinic') {
            Filament::setTenant($clinicFromHost, isQuiet: true);
        }

        return $next($request);
    }

    private function resolveRouteClinic(Request $request): ?Clinic
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Clinic) {
            return $tenant;
        }

        if (is_string($tenant) && $tenant !== '') {
            return Clinic::query()->where('slug', $tenant)->first();
        }

        $clinicSlug = $request->route('clinicSlug');

        if (is_string($clinicSlug) && $clinicSlug !== '') {
            return Clinic::query()->where('slug', $clinicSlug)->first();
        }

        return null;
    }
}
