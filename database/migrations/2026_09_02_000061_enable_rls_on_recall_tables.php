<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'recall_types',
            'recall_policy_versions',
            'patient_risk_assessments',
            'patient_recall_plans',
            'patient_recall_events',
            'treatment_plan_follow_ups',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation_policy ON {$table} USING (clinic_id = current_setting('app.current_clinic_id')::bigint)");
        }
    }

    public function down(): void
    {
        $tables = [
            'treatment_plan_follow_ups',
            'patient_recall_events',
            'patient_recall_plans',
            'patient_risk_assessments',
            'recall_policy_versions',
            'recall_types',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
