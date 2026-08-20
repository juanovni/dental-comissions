<?php

namespace App\Console\Commands;

use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetCurrentClinicCommand extends Command
{
    protected $signature = 'app:set-current-clinic {clinic? : Clinic ID to set as current tenant for RLS}
                            {--clear : Clear the current tenant RLS context}';

    protected $description = 'Set or clear the PostgreSQL RLS tenant context (app.current_clinic_id) for artisan commands and jobs';

    public function handle(TenantContext $tenantContext): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('Not PostgreSQL. RLS context not applicable.');
            return self::SUCCESS;
        }

        if ($this->option('clear')) {
            DB::statement("SET app.current_clinic_id = ''");
            $this->info('RLS tenant context cleared.');
            return self::SUCCESS;
        }

        $clinicId = $this->argument('clinic');

        if ($clinicId === null) {
            $currentVal = DB::selectOne("SELECT current_setting('app.current_clinic_id', true) as val")->val;
            $this->info('Current app.current_clinic_id: ' . ($currentVal ?: '(empty/null)'));
            return self::SUCCESS;
        }

        $clinicId = (int) $clinicId;

        $exists = DB::table('clinics')->where('id', $clinicId)->exists();
        if (! $exists) {
            $this->error("Clinic ID {$clinicId} not found.");
            return self::FAILURE;
        }

        DB::statement("SET app.current_clinic_id = '{$clinicId}'");
        $this->info("RLS tenant context set to clinic #{$clinicId}.");
        return self::SUCCESS;
    }
}