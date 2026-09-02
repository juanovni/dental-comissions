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
    case PatientFlowReceptionView = 'patient_flow_reception.view';
    case PatientFlowAssistantView = 'patient_flow_assistant.view';
    case PatientFlowDoctorView = 'patient_flow_doctor.view';
    case PatientFlowAdminView = 'patient_flow_admin.view';
    case SocialAccountsView = 'social_accounts.view';
    case SocialCommentsView = 'social_comments.view';
    case VoiceCallsView = 'voice_calls.view';
    case VoiceTestSimulatorView = 'voice_test_simulator.view';
    case SocialCrmSettingsView = 'social_crm_settings.view';
    case LocalLanguagePatternsView = 'local_language_patterns.view';

    // Historia clinica
    case MedicalProfilesView = 'medical_profiles.view';
    case MedicalProfilesCreate = 'medical_profiles.create';
    case MedicalProfilesUpdate = 'medical_profiles.update';
    case MedicalProfilesReview = 'medical_profiles.review';

    // Alertas clinicas
    case ClinicalAlertsView = 'clinical_alerts.view';
    case ClinicalAlertsCreate = 'clinical_alerts.create';
    case ClinicalAlertsResolve = 'clinical_alerts.resolve';

    // Encuentros clinicos
    case ClinicalEncountersView = 'clinical_encounters.view';
    case ClinicalEncountersCreate = 'clinical_encounters.create';
    case ClinicalEncountersSign = 'clinical_encounters.sign';
    case ClinicalEncountersAmend = 'clinical_encounters.amend';

    // Procedimientos realizados
    case PerformedProceduresView = 'performed_procedures.view';
    case PerformedProceduresRecord = 'performed_procedures.record';
    case PerformedProceduresCorrect = 'performed_procedures.correct';

    // Odontograma
    case OdontogramsView = 'odontograms.view';
    case OdontogramsUpdate = 'odontograms.update';

    // Planes de tratamiento
    case TreatmentPlansView = 'treatment_plans.view';
    case TreatmentPlansCreate = 'treatment_plans.create';
    case TreatmentPlansUpdateClinical = 'treatment_plans.update_clinical';
    case TreatmentPlansUpdatePricing = 'treatment_plans.update_pricing';
    case TreatmentPlansApprove = 'treatment_plans.approve';
    case TreatmentPlansSend = 'treatment_plans.send';

    // Consentimientos
    case InformedConsentsView = 'informed_consents.view';
    case InformedConsentsManage = 'informed_consents.manage';

    // Autorizaciones externas
    case ExternalClearanceView = 'external_clearance.view';
    case ExternalClearanceManage = 'external_clearance.manage';

    // Recall clinico
    case ClinicalRecallView = 'clinical_recall.view';
    case ClinicalRecallManage = 'clinical_recall.manage';

    // Seguridad clinica
    case SafetyRulesView = 'safety_rules.view';
    case SafetyRulesManage = 'safety_rules.manage';

    // Especialidades
    case SpecialtiesView = 'specialties.view';
    case SpecialtiesManage = 'specialties.manage';

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
            self::PatientFlowReceptionView => 'Ver panel de recepcion',
            self::PatientFlowAssistantView => 'Ver cola clinica',
            self::PatientFlowDoctorView => 'Ver mi cola de atencion',
            self::PatientFlowAdminView => 'Ver operacion clinica',
            self::SocialAccountsView => 'Ver cuentas sociales',
            self::SocialCommentsView => 'Ver casos sociales',
            self::VoiceCallsView => 'Ver llamadas de voz',
            self::VoiceTestSimulatorView => 'Ver simulador de llamada',
            self::SocialCrmSettingsView => 'Ver configuracion CRM social',
            self::LocalLanguagePatternsView => 'Ver lenguaje local',
            // Historia clinica
            self::MedicalProfilesView => 'Ver historias clinicas',
            self::MedicalProfilesCreate => 'Crear historias clinicas',
            self::MedicalProfilesUpdate => 'Actualizar historias clinicas',
            self::MedicalProfilesReview => 'Revisar historias clinicas',
            // Alertas clinicas
            self::ClinicalAlertsView => 'Ver alertas clinicas',
            self::ClinicalAlertsCreate => 'Crear alertas clinicas',
            self::ClinicalAlertsResolve => 'Resolver alertas clinicas',
            // Encuentros clinicos
            self::ClinicalEncountersView => 'Ver encuentros clinicos',
            self::ClinicalEncountersCreate => 'Crear encuentros clinicos',
            self::ClinicalEncountersSign => 'Firmar encuentros clinicos',
            self::ClinicalEncountersAmend => 'Enmendar encuentros clinicos',
            // Procedimientos realizados
            self::PerformedProceduresView => 'Ver procedimientos realizados',
            self::PerformedProceduresRecord => 'Registrar procedimientos realizados',
            self::PerformedProceduresCorrect => 'Corregir procedimientos realizados',
            // Odontograma
            self::OdontogramsView => 'Ver odontogramas',
            self::OdontogramsUpdate => 'Actualizar odontogramas',
            // Planes de tratamiento
            self::TreatmentPlansView => 'Ver planes de tratamiento',
            self::TreatmentPlansCreate => 'Crear planes de tratamiento',
            self::TreatmentPlansUpdateClinical => 'Actualizar clinica de planes',
            self::TreatmentPlansUpdatePricing => 'Actualizar precios de planes',
            self::TreatmentPlansApprove => 'Aprobar planes de tratamiento',
            self::TreatmentPlansSend => 'Enviar planes de tratamiento',
            // Consentimientos
            self::InformedConsentsView => 'Ver consentimientos informados',
            self::InformedConsentsManage => 'Gestionar consentimientos informados',
            // Autorizaciones externas
            self::ExternalClearanceView => 'Ver autorizaciones externas',
            self::ExternalClearanceManage => 'Gestionar autorizaciones externas',
            // Recall clinico
            self::ClinicalRecallView => 'Ver recall clinico',
            self::ClinicalRecallManage => 'Gestionar recall clinico',
            // Seguridad clinica
            self::SafetyRulesView => 'Ver reglas de seguridad clinica',
            self::SafetyRulesManage => 'Gestionar reglas de seguridad clinica',
            // Especialidades
            self::SpecialtiesView => 'Ver especialidades',
            self::SpecialtiesManage => 'Gestionar especialidades',
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
            self::AppointmentsView,
            self::PatientFlowReceptionView,
            self::PatientFlowAssistantView,
            self::PatientFlowDoctorView,
            self::PatientFlowAdminView => 'Operacion clinica',
            self::SocialAccountsView,
            self::SocialCommentsView,
            self::IntegrationsView,
            self::SocialInboxView,
            self::SocialPipelineView,
            self::SocialCrmSettingsView => 'CRM social',
            self::LocalLanguagePatternsView => 'Configuracion',
            self::VoiceCallsView,
            self::VoiceTestSimulatorView => 'Pity Voice',
            // Historia clinica
            self::MedicalProfilesView,
            self::MedicalProfilesCreate,
            self::MedicalProfilesUpdate,
            self::MedicalProfilesReview => 'Historia clinica',
            // Alertas clinicas
            self::ClinicalAlertsView,
            self::ClinicalAlertsCreate,
            self::ClinicalAlertsResolve => 'Alertas clinicas',
            // Encuentros clinicos
            self::ClinicalEncountersView,
            self::ClinicalEncountersCreate,
            self::ClinicalEncountersSign,
            self::ClinicalEncountersAmend => 'Encuentros clinicos',
            // Procedimientos realizados
            self::PerformedProceduresView,
            self::PerformedProceduresRecord,
            self::PerformedProceduresCorrect => 'Procedimientos realizados',
            // Odontograma
            self::OdontogramsView,
            self::OdontogramsUpdate => 'Odontograma',
            // Planes de tratamiento
            self::TreatmentPlansView,
            self::TreatmentPlansCreate,
            self::TreatmentPlansUpdateClinical,
            self::TreatmentPlansUpdatePricing,
            self::TreatmentPlansApprove,
            self::TreatmentPlansSend => 'Planes de tratamiento',
            // Consentimientos
            self::InformedConsentsView,
            self::InformedConsentsManage => 'Consentimientos informados',
            // Autorizaciones externas
            self::ExternalClearanceView,
            self::ExternalClearanceManage => 'Autorizaciones externas',
            // Recall clinico
            self::ClinicalRecallView,
            self::ClinicalRecallManage => 'Recall clinico',
            // Seguridad clinica
            self::SafetyRulesView,
            self::SafetyRulesManage => 'Seguridad clinica',
            // Especialidades
            self::SpecialtiesView,
            self::SpecialtiesManage => 'Especialidades',
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
