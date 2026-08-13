<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncFilamentTenantContext
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($tenant = Filament::getTenant()) {
            $this->tenantContext->set($tenant);
        }

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}
