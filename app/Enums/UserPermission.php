<?php

namespace App\Enums;

enum UserPermission: string
{
    case ProfessionalsView = 'professionals.view';
    case PatientsView = 'patients.view';
    case ProceduresView = 'procedures.view';
    case DoctorAssistantAssignmentsView = 'doctor_assistant_assignments.view';
    case AppointmentsView = 'appointments.view';
    case WhatsappMessagesView = 'whatsapp_messages.view';
    case SocialAccountsView = 'social_accounts.view';
    case SocialCommentsView = 'social_comments.view';
    case VoiceCallsView = 'voice_calls.view';
    case SocialCrmSettingsView = 'social_crm_settings.view';

    public function label(): string
    {
        return match ($this) {
            self::ProfessionalsView => 'Ver profesionales',
            self::PatientsView => 'Ver contactos/pacientes',
            self::ProceduresView => 'Ver procedimientos',
            self::DoctorAssistantAssignmentsView => 'Ver asignaciones doctor-asistente',
            self::AppointmentsView => 'Ver agenda/citas',
            self::WhatsappMessagesView => 'Ver mensajes WhatsApp',
            self::SocialAccountsView => 'Ver cuentas sociales',
            self::SocialCommentsView => 'Ver casos sociales',
            self::VoiceCallsView => 'Ver llamadas de voz',
            self::SocialCrmSettingsView => 'Ver configuracion CRM social',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ProfessionalsView,
            self::ProceduresView,
            self::DoctorAssistantAssignmentsView => 'Configuracion operativa',
            self::PatientsView,
            self::AppointmentsView,
            self::WhatsappMessagesView => 'Operacion clinica',
            self::SocialAccountsView,
            self::SocialCommentsView,
            self::SocialCrmSettingsView => 'CRM social',
            self::VoiceCallsView => 'Pity Voice',
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
