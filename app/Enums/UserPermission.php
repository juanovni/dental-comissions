<?php

namespace App\Enums;

enum UserPermission: string
{
    case ProfessionalsView = 'professionals.view';
    case PatientsView = 'patients.view';
    case ProceduresView = 'procedures.view';
    case DoctorAssistantAssignmentsView = 'doctor_assistant_assignments.view';
    case ActivityRecordsView = 'activity_records.view';
    case AppointmentsView = 'appointments.view';
    case WhatsappMessagesView = 'whatsapp_messages.view';
    case SocialAccountsView = 'social_accounts.view';
    case SocialCommentsView = 'social_comments.view';
    case VoiceCallsView = 'voice_calls.view';
    case SocialCrmSettingsView = 'social_crm_settings.view';
    case PaymentMethodsView = 'payment_methods.view';
    case PaymentMethodCommissionRatesView = 'payment_method_commission_rates.view';
    case WeeklyReportsView = 'weekly_reports.view';

    public function label(): string
    {
        return match ($this) {
            self::ProfessionalsView => 'Ver profesionales',
            self::PatientsView => 'Ver contactos/pacientes',
            self::ProceduresView => 'Ver procedimientos',
            self::DoctorAssistantAssignmentsView => 'Ver asignaciones doctor-asistente',
            self::ActivityRecordsView => 'Ver actividades clinicas',
            self::AppointmentsView => 'Ver agenda/citas',
            self::WhatsappMessagesView => 'Ver mensajes WhatsApp',
            self::SocialAccountsView => 'Ver cuentas sociales',
            self::SocialCommentsView => 'Ver comentarios sociales',
            self::VoiceCallsView => 'Ver llamadas de voz',
            self::SocialCrmSettingsView => 'Ver configuracion CRM social',
            self::PaymentMethodsView => 'Ver metodos de pago',
            self::PaymentMethodCommissionRatesView => 'Ver tarifas por metodo de pago',
            self::WeeklyReportsView => 'Ver reportes semanales',
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
            self::ActivityRecordsView,
            self::WhatsappMessagesView => 'Operacion clinica',
            self::SocialAccountsView,
            self::SocialCommentsView,
            self::SocialCrmSettingsView => 'CRM social',
            self::VoiceCallsView => 'Pity Voice',
            self::PaymentMethodsView,
            self::PaymentMethodCommissionRatesView,
            self::WeeklyReportsView => 'Finanzas heredadas',
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
