<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class SyncFilamentTenantContext
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if (! $tenant && Filament::getCurrentPanel()?->getId() === 'clinic') {
            $user = $request->user();

            if ($user && method_exists($user, 'getDefaultTenant')) {
                $tenant = $user->getDefaultTenant(Filament::getCurrentPanel());

                if ($tenant instanceof Model) {
                    Filament::setTenant($tenant, isQuiet: true);
                }
            }
        }

        if ($tenant) {
            $this->tenantContext->set($tenant);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
