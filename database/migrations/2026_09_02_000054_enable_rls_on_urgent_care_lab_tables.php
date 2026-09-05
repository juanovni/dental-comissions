<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'urgent_care_intakes',
            'dental_laboratory_cases',
            'dental_laboratory_case_items',
            'dental_laboratory_events',
            'dental_laboratory_documents',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation_policy ON {$table} USING (clinic_id = current_setting('app.current_clinic_id')::bigint)");
        }
    }

    public function down(): void
    {
        $tables = [
            'dental_laboratory_documents',
            'dental_laboratory_events',
            'dental_laboratory_case_items',
            'dental_laboratory_cases',
            'urgent_care_intakes',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_policy ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
