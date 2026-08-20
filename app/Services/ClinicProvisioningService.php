<?php

namespace App\Services;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\SocialCrmSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ClinicProvisioningService
{
    /**
     * @param  array<string, mixed>  $clinicData
     * @param  array<string, mixed>|null  $newAdminData
     */
    public function provision(array $clinicData, ?User $existingAdmin = null, ?array $newAdminData = null): Clinic
    {
        if ($existingAdmin === null && $newAdminData === null) {
            throw new \InvalidArgumentException('Debe existir un admin inicial para provisionar la clinica.');
        }

        $clinic = Clinic::create([
            'name' => $clinicData['name'],
            'slug' => $clinicData['slug'],
            'subdomain' => $clinicData['subdomain'],
            'primary_domain' => $clinicData['primary_domain'] ?? $this->buildPrimaryDomain((string) $clinicData['subdomain']),
            'country' => $clinicData['country'] ?? null,
            'currency' => $clinicData['currency'] ?? 'USD',
            'timezone' => $clinicData['timezone'] ?? 'America/Guayaquil',
            'status' => TenantStatus::Provisioning,
            'settings' => null,
        ]);

        try {
            DB::transaction(function () use ($clinic, $existingAdmin, $newAdminData): void {
                $admin = $existingAdmin ?? $this->createAdmin($newAdminData ?? []);

                $clinic->users()->syncWithoutDetaching([
                    $admin->id => [
                        'role' => UserRole::Admin->value,
                        'is_default' => true,
                        'is_active' => true,
                        'permissions' => null,
                    ],
                ]);

                $clinic->update([
                    'status' => TenantStatus::Active,
                    'settings' => $this->defaultSettings($clinic),
                ]);

                SocialCrmSetting::create([
                    'clinic_id' => $clinic->id,
                    'setting_group' => 'citas',
                    'key' => 'social_appointment_clinic_timezone',
                    'label' => 'Zona horaria de la clínica',
                    'value_type' => 'string',
                    'value' => $clinic->timezone,
                    'is_active' => true,
                ]);
            });
        } catch (Throwable $exception) {
            $clinic->forceFill([
                'status' => TenantStatus::ProvisioningFailed,
                'settings' => array_merge($clinic->settings ?? [], [
                    'provisioning_error' => $exception->getMessage(),
                ]),
            ])->save();

            throw $exception;
        }

        return $clinic->fresh(['users']);
    }

    private function createAdmin(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? Hash::make('password'),
            'role' => $data['role'] ?? UserRole::Admin,
            'is_active' => Arr::get($data, 'is_active', true),
        ]);
    }

    private function buildPrimaryDomain(string $subdomain): string
    {
        return $subdomain.'.'.config('tenancy.base_domain', 'localhost');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(Clinic $clinic): array
    {
        return [
            'locale' => config('app.locale', 'es'),
            'storage_prefix' => 'clinics/'.$clinic->id.'/',
            'appointments' => [
                'workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'start_time' => '09:00',
                'end_time' => '17:00',
                'slot_interval_minutes' => 30,
                'default_duration_minutes' => 30,
            ],
            'crm' => [
                'auto_responses_enabled' => false,
                'automatic_mode_enabled' => false,
                'ai_classification_mode' => 'review',
                'internal_alerts_enabled' => false,
                'templates_seeded' => 'examples',
            ],
            // Procedures become tenant records in phase 2 once procedures has clinic_id.
            'procedure_templates' => [
                [
                    'name' => 'Consulta inicial',
                    'price' => 0,
                    'duration_minutes' => 30,
                ],
            ],
            'integrations' => [
                'meta' => 'not_configured',
                'whatsapp' => 'not_configured',
                'google_calendar' => 'not_configured',
                'telnyx' => 'not_configured',
            ],
        ];
    }
}
