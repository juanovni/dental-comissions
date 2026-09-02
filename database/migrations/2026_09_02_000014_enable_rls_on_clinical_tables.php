<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'specialties',
            'patient_medical_profiles',
            'patient_medical_profile_versions',
            'patient_allergies',
            'patient_medications',
            'patient_medical_conditions',
            'patient_clinical_alerts',
            'medical_profile_reviews',
            'clinical_safety_rule_sets',
            'clinical_safety_rule_set_versions',
            'clinical_safety_rules',
            'clinical_safety_rule_applications',
            'clinical_readiness_evaluations',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY tenant_isolation_policy ON {$table}
                    USING (clinic_id = current_setting('app.current_clinic_id')::bigint)
                    WITH CHECK (clinic_id = current_setting('app.current_clinic_id')::bigint)
            ");
        }
    }

    public function down(): void
    {
        $tables = [
            'specialties',
            'patient_medical_profiles',
            'patient_medical_profile_versions',
            'patient_allergies',
            'patient_medications',
            'patient_medical_conditions',
            'patient_clinical_alerts',
            'medical_profile_reviews',
            'clinical_safety_rule_sets',
            'clinical_safety_rule_set_versions',
            'clinical_safety_rules',
            'clinical_safety_rule_applications',
            'clinical_readiness_evaluations',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        }
    }
};
