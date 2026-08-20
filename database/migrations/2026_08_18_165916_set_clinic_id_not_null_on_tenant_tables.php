<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql' && $driver !== 'mysql') {
            return;
        }

        // Skip in test environments: no clinics means fresh/migrated test DB
        if (DB::table('clinics')->count() === 0) {
            return;
        }

        $firstClinicId = DB::table('clinics')->value('id');
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }
            $nullCount = DB::table($table)->whereNull('clinic_id')->count();
            if ($nullCount > 0) {
                if ($firstClinicId !== null) {
                    DB::table($table)->whereNull('clinic_id')->update(['clinic_id' => $firstClinicId]);
                } else {
                    DB::table($table)->whereNull('clinic_id')->delete();
                }
            }
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN clinic_id SET NOT NULL");
            } elseif ($driver === 'mysql') {
                $col = DB::selectOne("SELECT DATA_TYPE, IS_NULLABLE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'clinic_id'", [$table]);
                if ($col) {
                    DB::statement("ALTER TABLE {$table} MODIFY COLUMN clinic_id {$col->COLUMN_TYPE} NOT NULL");
                }
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'pgsql' && $driver !== 'mysql') {
            return;
        }

        if (DB::table('clinics')->count() === 0) {
            return;
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN clinic_id DROP NOT NULL");
            } elseif ($driver === 'mysql') {
                $col = DB::selectOne("SELECT DATA_TYPE, IS_NULLABLE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'clinic_id'", [$table]);
                if ($col) {
                    DB::statement("ALTER TABLE {$table} MODIFY COLUMN clinic_id {$col->COLUMN_TYPE} NULL");
                }
            }
        }
    }
};