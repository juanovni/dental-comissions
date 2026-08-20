<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use App\Services\ClinicProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $existing = Clinic::query()->where('slug', 'clinic-1')->first();

        if ($existing === null) {
            $seedAdmin = User::query()->orderByRaw("case when role = 'super_admin' then 0 else 1 end")
                ->orderBy('id')
                ->first();

            if ($seedAdmin === null) {
                return;
            }

            $clinic = app(ClinicProvisioningService::class)->provision([
                'name' => 'Clinic #1',
                'slug' => 'clinic-1',
                'subdomain' => 'clinic-1',
                'currency' => 'USD',
                'timezone' => 'America/Guayaquil',
            ], existingAdmin: $seedAdmin);
        } else {
            $clinic = $existing;
        }

        User::query()->each(function (User $user) use ($clinic): void {
            $role = $user->isSuperAdmin() ? UserRole::Admin : $user->role;

            $clinic->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => $role instanceof UserRole ? $role->value : UserRole::Admin->value,
                    'is_default' => true,
                    'is_active' => true,
                    'permissions' => null,
                ],
            ]);
        });

        $this->backfillCoreTables($clinic);
    }

    private function backfillCoreTables(Clinic $clinic): void
    {
        foreach ([
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
        ] as $table) {
            if (! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('clinic_id')
                ->update(['clinic_id' => $clinic->id]);
        }

        foreach ([
            'appointment_events',
            'appointment_notes',
            'appointment_reminders',
            'appointment_check_in_attempts',
            'appointment_slot_offers',
            'appointment_slot_holds',
            'doctor_assistant_assignments',
        ] as $table) {
            if (! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('clinic_id')
                ->update(['clinic_id' => $clinic->id]);
        }
    }
}
