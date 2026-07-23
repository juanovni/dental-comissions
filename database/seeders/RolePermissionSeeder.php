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
            UserRole::Admin->value => [
                UserPermission::ProfessionalsView,
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::ActivityRecordsView,
                UserPermission::AppointmentsView,
                UserPermission::WhatsappMessagesView,
                UserPermission::SocialAccountsView,
                UserPermission::SocialCommentsView,
                UserPermission::VoiceCallsView,
                UserPermission::SocialCrmSettingsView,
            ],
            UserRole::Doctor->value => [
                UserPermission::ProfessionalsView,
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::ActivityRecordsView,
                UserPermission::AppointmentsView,
                UserPermission::WhatsappMessagesView,
            ],
            UserRole::Assistant->value => [
                UserPermission::ProfessionalsView,
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::DoctorAssistantAssignmentsView,
                UserPermission::ActivityRecordsView,
                UserPermission::AppointmentsView,
                UserPermission::WhatsappMessagesView,
            ],
            UserRole::Receptionist->value => [
                UserPermission::PatientsView,
                UserPermission::ProceduresView,
                UserPermission::AppointmentsView,
                UserPermission::WhatsappMessagesView,
                UserPermission::SocialCommentsView,
            ],
        ];

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
