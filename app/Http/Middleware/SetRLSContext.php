<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetRLSContext
{
    public function __construct(private TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $next($request);
        }

        $clinicId = $this->tenantContext->id();

        if ($clinicId !== null) {
            DB::statement("SET app.current_clinic_id = '{$clinicId}'");
        }

        try {
            return $next($request);
        } finally {
            DB::statement("SET app.current_clinic_id = ''");
        }
    }
}