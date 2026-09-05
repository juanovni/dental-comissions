<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'external_medical_consultations',
            'medical_clearance_decisions',
            'medical_clearance_conditions',
            'treatment_plan_item_clearance_requirements',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation_policy ON {$table} USING (clinic_id = current_setting('app.current_clinic_id')::bigint)");
        }
    }

    public function down(): void
    {
        $tables = [
            'treatment_plan_item_clearance_requirements',
            'medical_clearance_conditions',
            'medical_clearance_decisions',
            'external_medical_consultations',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
