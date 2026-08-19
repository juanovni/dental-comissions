<?php

namespace App\Console\Commands\Concerns;

use App\Models\Clinic;
use App\Support\TenantContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait RunsForEachClinic
{
    protected function runForEachClinic(Closure $callback): int
    {
        /** @var string|null $clinicOption */
        $clinicOption = $this->option('clinic');
        $tenantContext = app(TenantContext::class);

        if ($clinicOption !== null) {
            $clinic = Clinic::query()->findOrFail((int) $clinicOption);

            $tenantContext->run($clinic, fn () => $this->runWithRLS($clinic, $callback));

            return self::SUCCESS;
        }

        $clinics = Clinic::query()->where('status', 'active')->get();
        $errors = 0;

        foreach ($clinics as $clinic) {
            try {
                $tenantContext->run($clinic, fn () => $this->runWithRLS($clinic, $callback));
            } catch (\Throwable $e) {
                report($e);
                Log::error("Error en clinica {$clinic->name} ({$clinic->id})", [
                    'command' => static::class,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error en clinica {$clinic->name} ({$clinic->id}): {$e->getMessage()}");
                $errors++;
            }
        }

        if ($errors > 0) {
            $this->warn("Completed with {$errors} error(s).");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runWithRLS(Clinic $clinic, Closure $callback): mixed
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET app.current_clinic_id = '{$clinic->getKey()}'");
        }

        return $callback($clinic);
    }
}
