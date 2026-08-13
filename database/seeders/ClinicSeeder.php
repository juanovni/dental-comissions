<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $subdomain = 'clinic-1';
        $baseDomain = config('tenancy.base_domain', 'localhost');

        $clinic = Clinic::updateOrCreate(
            ['slug' => 'clinic-1'],
            [
                'name' => 'Clinic #1',
                'subdomain' => $subdomain,
                'primary_domain' => $subdomain.'.'.$baseDomain,
                'country' => null,
                'currency' => 'USD',
                'timezone' => config('app.timezone', 'UTC'),
                'status' => TenantStatus::Active,
            ],
        );

        $clinic->update([
            'settings' => [
                'storage_prefix' => 'clinics/'.$clinic->id.'/',
            ],
        ]);

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
    }
}
