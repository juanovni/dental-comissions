<?php

namespace App\Enums;

enum UserPermission: string
{
    case DashboardRoiSocialView = 'dashboard_roi_social.view';
    case SocialInboxView = 'social_inbox.view';
    case SocialPipelineView = 'social_pipeline.view';
    case IntegrationsView = 'integrations.view';
    case ProfessionalsView = 'professionals.view';
    case PatientsView = 'patients.view';
    case ProceduresView = 'procedures.view';
    case DoctorAssistantAssignmentsView = 'doctor_assistant_assignments.view';
    case AppointmentsView = 'appointments.view';
    case SocialAccountsView = 'social_accounts.view';
    case SocialCommentsView = 'social_comments.view';
    case VoiceCallsView = 'voice_calls.view';
    case VoiceTestSimulatorView = 'voice_test_simulator.view';
    case SocialCrmSettingsView = 'social_crm_settings.view';

    public function label(): string
    {
        return match ($this) {
            self::DashboardRoiSocialView => 'Ver dashboard ROI Social',
            self::SocialInboxView => 'Ver bandeja social',
            self::SocialPipelineView => 'Ver pipeline social',
            self::IntegrationsView => 'Ver integraciones',
            self::ProfessionalsView => 'Ver profesionales',
            self::PatientsView => 'Ver contactos/pacientes',
            self::ProceduresView => 'Ver procedimientos',
            self::DoctorAssistantAssignmentsView => 'Ver asignaciones doctor-asistente',
            self::AppointmentsView => 'Ver agenda/citas',
            self::SocialAccountsView => 'Ver cuentas sociales',
            self::SocialCommentsView => 'Ver casos sociales',
            self::VoiceCallsView => 'Ver llamadas de voz',
            self::VoiceTestSimulatorView => 'Ver simulador de llamada',
            self::SocialCrmSettingsView => 'Ver configuracion CRM social',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardRoiSocialView => 'Dashboards',
            self::ProfessionalsView,
            self::ProceduresView,
            self::DoctorAssistantAssignmentsView => 'Configuracion operativa',
            self::PatientsView,
            self::AppointmentsView => 'Operacion clinica',
            self::SocialAccountsView,
            self::SocialCommentsView,
            self::IntegrationsView,
            self::SocialInboxView,
            self::SocialPipelineView,
            self::SocialCrmSettingsView => 'CRM social',
            self::VoiceCallsView,
            self::VoiceTestSimulatorView => 'Pity Voice',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $permission): array => [$permission->value => $permission->label()])
            ->all();
    }
}
