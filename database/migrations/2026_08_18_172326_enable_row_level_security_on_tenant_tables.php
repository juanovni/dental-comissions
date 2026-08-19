<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'patients',
        'professionals',
        'appointments',
        'procedures',
        'appointment_events',
        'appointment_notes',
        'appointment_reminders',
        'appointment_check_in_attempts',
        'appointment_slot_offers',
        'appointment_slot_holds',
        'doctor_assistant_assignments',
        'social_accounts',
        'social_posts',
        'social_comments',
        'social_identities',
        'social_comment_actions',
        'social_lead_alerts',
        'social_link_events',
        'social_reply_templates',
        'social_moderation_rules',
        'social_crm_settings',
        'whatsapp_messages',
        'calendar_integrations',
        'voice_calls',
        'voice_events',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (DB::table('clinics')->count() === 0) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION current_clinic_id() RETURNS bigint AS $$
                SELECT nullif(current_setting('app.current_clinic_id', true), '')::bigint;
            $$ LANGUAGE sql STABLE
        ");

        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY tenant_isolation_{$table} ON {$table}
                    USING (clinic_id = current_clinic_id() OR current_clinic_id() IS NULL)
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }

        DB::statement("DROP FUNCTION IF EXISTS current_clinic_id()");
    }
};