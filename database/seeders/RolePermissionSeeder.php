<?php

namespace Database\Seeders;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $enabledByRole = [
            UserRole::SuperAdmin->value => UserPermission::cases(),
            UserRole::Admin->value => [
                UserPermission::DashboardRoiSocialView,
                UserPermission::SocialInboxView,
                UserPermission::SocialPipelineView,
                UserPermission::IntegrationsView,
                UserPermission::ProfessionalsView,
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::AppointmentsView,
                UserPermission::SocialAccountsView,
                UserPermission::SocialCrmSettingsView,
                UserPermission::LocalLanguagePatternsView,
            ],
            UserRole::Doctor->value => [
                UserPermission::DashboardRoiSocialView,
                UserPermission::ProfessionalsView,
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::AppointmentsView,
            ],
            UserRole::Assistant->value => [
                UserPermission::DashboardRoiSocialView,
                UserPermission::PatientsView,
                UserPermission::AppointmentsView,
            ],
            UserRole::Receptionist->value => [
                UserPermission::DashboardRoiSocialView,
                UserPermission::SocialInboxView,
                UserPermission::SocialPipelineView,
                UserPermission::PatientsView,
                UserPermission::ProfessionalsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::AppointmentsView,
            ],
        ];

        RolePermission::query()
            ->whereNotIn('permission', collect(UserPermission::cases())->map->value)
            ->delete();

        foreach (UserRole::cases() as $role) {
            foreach (UserPermission::cases() as $permission) {
                RolePermission::updateOrCreate(
                    [
                        'role' => $role->value,
                        'permission' => $permission->value,
                    ],
                    [
                        'is_enabled' => in_array($permission, $enabledByRole[$role->value] ?? [], true),
                    ],
                );
            }
        }
    }
}
