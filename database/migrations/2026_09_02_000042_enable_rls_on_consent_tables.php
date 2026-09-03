<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'informed_consent_templates',
            'informed_consent_template_versions',
            'treatment_plan_item_consent_requirements',
            'treatment_plan_item_consents',
            'informed_consent_events',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation_policy ON {$table} USING (clinic_id = current_setting('app.current_clinic_id')::bigint)");
        }
    }

    public function down(): void
    {
        $tables = [
            'informed_consent_events',
            'treatment_plan_item_consents',
            'treatment_plan_item_consent_requirements',
            'informed_consent_template_versions',
            'informed_consent_templates',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
