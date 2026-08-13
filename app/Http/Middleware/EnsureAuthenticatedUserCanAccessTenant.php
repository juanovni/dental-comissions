<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedUserCanAccessTenant
{
    public function __construct(private TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->get();
        $user = $request->user();

        if (($tenant !== null) && ($user instanceof User) && (! $user->canAccessTenant($tenant))) {
            abort(403);
        }

        return $next($request);
    }
}
