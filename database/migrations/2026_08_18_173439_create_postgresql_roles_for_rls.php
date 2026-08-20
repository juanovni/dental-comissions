<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tenantTables = [
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

        $hasAppRole = DB::select("SELECT 1 FROM pg_roles WHERE rolname = 'dental_app'");
        if (empty($hasAppRole)) {
            DB::statement("CREATE ROLE dental_app LOGIN PASSWORD 'dental_app'");
        }

        DB::statement("GRANT USAGE ON SCHEMA public TO dental_app");

        foreach ($this->tenantTables as $table) {
            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON {$table} TO dental_app");
        }

        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO dental_app");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO dental_app");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE ON SEQUENCES TO dental_app");

        $hasBypassRole = DB::select("SELECT 1 FROM pg_roles WHERE rolname = 'dental_bypass'");
        if (empty($hasBypassRole)) {
            DB::statement("CREATE ROLE dental_bypass LOGIN PASSWORD 'dental_bypass'");
        }

        DB::statement("ALTER ROLE dental_bypass BYPASSRLS");
        DB::statement("GRANT USAGE ON SCHEMA public TO dental_bypass");

        foreach ($this->tenantTables as $table) {
            DB::statement("GRANT ALL PRIVILEGES ON {$table} TO dental_bypass");
        }

        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO dental_bypass");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DROP ROLE IF EXISTS dental_bypass");
        DB::statement("DROP ROLE IF EXISTS dental_app");
    }
};