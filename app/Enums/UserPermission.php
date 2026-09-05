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

    // Casos clinicos
    case ClinicalCasesView = 'clinical_cases.view';
    case ClinicalCasesCreate = 'clinical_cases.create';
    case ClinicalCasesUpdate = 'clinical_cases.update';
    case ClinicalCasesClose = 'clinical_cases.close';

    // Encuentros clinicos
    case ClinicalEncountersView = 'clinical_encounters.view';
    case ClinicalEncountersCreate = 'clinical_encounters.create';
    case ClinicalEncountersSign = 'clinical_encounters.sign';
    case ClinicalEncountersAmend = 'clinical_encounters.amend';

    // Procedimientos realizados
    case PerformedProceduresView = 'performed_procedures.view';
    case PerformedProceduresRecord = 'performed_procedures.record';
    case PerformedProceduresCorrect = 'performed_procedures.correct';

    // Enmiendas clinicas
    case ClinicalAmendmentsView = 'clinical_amendments.view';
    case ClinicalAmendmentsCreate = 'clinical_amendments.create';
    case ClinicalAmendmentsApprove = 'clinical_amendments.approve';

    // Odontograma
    case OdontogramsView = 'odontograms.view';
    case OdontogramsCreate = 'odontograms.create';
    case OdontogramsUpdateDraft = 'odontograms.update_draft';
    case OdontogramsSign = 'odontograms.sign';
    case OdontogramsAmend = 'odontograms.amend';
    case OdontogramsAddFinding = 'odontograms.add_finding';
    case OdontogramsAddDiagnosis = 'odontograms.add_diagnosis';
    case OdontogramsPlanTreatment = 'odontograms.plan_treatment';
    case OdontogramsViewHistory = 'odontograms.view_history';

    // Planes de tratamiento
    case TreatmentPlansView = 'treatment_plans.view';
    case TreatmentPlansCreate = 'treatment_plans.create';
    case TreatmentPlansUpdateClinical = 'treatment_plans.update_clinical';
    case TreatmentPlansUpdatePricing = 'treatment_plans.update_pricing';
    case TreatmentPlansApprove = 'treatment_plans.approve';
    case TreatmentPlansSend = 'treatment_plans.send';
    case TreatmentPlansDelete = 'treatment_plans.delete';

    // Consentimientos informados
    case InformedConsentsView = 'informed_consents.view';
    case InformedConsentsManage = 'informed_consents.manage';
    case InformedConsentTemplatesView = 'informed_consent_templates.view';
    case InformedConsentTemplatesCreate = 'informed_consent_templates.create';
    case InformedConsentTemplatesUpdate = 'informed_consent_templates.update';
    case InformedConsentTemplatesApprove = 'informed_consent_templates.approve';
    case InformedConsentTemplatesArchive = 'informed_consent_templates.archive';
    case InformedConsentRequirementsManage = 'informed_consent_requirements.manage';
    case InformedConsentSign = 'informed_consents.sign';
    case InformedConsentReject = 'informed_consents.reject';
    case InformedConsentRevoke = 'informed_consents.revoke';
    case InformedConsentWaive = 'informed_consents.waive';

    // Consultas externas y autorizaciones
    case ExternalClearanceView = 'external_clearance.view';
    case ExternalClearanceManage = 'external_clearance.manage';
    case ExternalConsultationsView = 'external_consultations.view';
    case ExternalConsultationsCreate = 'external_consultations.create';
    case ExternalConsultationsUpdate = 'external_consultations.update';
    case ExternalConsultationsSend = 'external_consultations.send';
    case ExternalConsultationsClose = 'external_consultations.close';
    case MedicalClearanceDecisionsCreate = 'medical_clearance_decisions.create';
    case MedicalClearanceDecisionsReview = 'medical_clearance_decisions.review';
    case MedicalClearanceConditionsVerify = 'medical_clearance_conditions.verify';
    case ClearanceRequirementsManage = 'clearance_requirements.manage';

    // Atencion urgente
    case UrgentCareView = 'urgent_care.view';
    case UrgentCareCreate = 'urgent_care.create';
    case UrgentCareUpdate = 'urgent_care.update';
    case UrgentCareDispose = 'urgent_care.dispose';

    // Laboratorio dental
    case LaboratoryCasesView = 'laboratory_cases.view';
    case LaboratoryCasesCreate = 'laboratory_cases.create';
    case LaboratoryCasesUpdate = 'laboratory_cases.update';
    case LaboratoryCasesManageItems = 'laboratory_cases.manage_items';
    case LaboratoryCasesManageEvents = 'laboratory_cases.manage_events';
    case LaboratoryCasesManageDocuments = 'laboratory_cases.manage_documents';

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
            // Casos clinicos
            self::ClinicalCasesView => 'Ver casos clinicos',
            self::ClinicalCasesCreate => 'Crear casos clinicos',
            self::ClinicalCasesUpdate => 'Actualizar casos clinicos',
            self::ClinicalCasesClose => 'Cerrar casos clinicos',
            // Encuentros clinicos
            self::ClinicalEncountersView => 'Ver encuentros clinicos',
            self::ClinicalEncountersCreate => 'Crear encuentros clinicos',
            self::ClinicalEncountersSign => 'Firmar encuentros clinicos',
            self::ClinicalEncountersAmend => 'Enmendar encuentros clinicos',
            // Procedimientos realizados
            self::PerformedProceduresView => 'Ver procedimientos realizados',
            self::PerformedProceduresRecord => 'Registrar procedimientos realizados',
            self::PerformedProceduresCorrect => 'Corregir procedimientos realizados',
            // Enmiendas clinicas
            self::ClinicalAmendmentsView => 'Ver enmiendas clinicas',
            self::ClinicalAmendmentsCreate => 'Crear enmiendas clinicas',
            self::ClinicalAmendmentsApprove => 'Aprobar enmiendas clinicas',
            // Odontograma
            self::OdontogramsView => 'Ver odontogramas',
            self::OdontogramsCreate => 'Crear odontogramas',
            self::OdontogramsUpdateDraft => 'Actualizar borradores de odontogramas',
            self::OdontogramsSign => 'Firmar odontogramas',
            self::OdontogramsAmend => 'Enmendar odontogramas',
            self::OdontogramsAddFinding => 'Agregar hallazgos al odontograma',
            self::OdontogramsAddDiagnosis => 'Agregar diagnosticos al odontograma',
            self::OdontogramsPlanTreatment => 'Planificar tratamientos desde odontograma',
            self::OdontogramsViewHistory => 'Ver historial de odontogramas',
            // Planes de tratamiento
            self::TreatmentPlansView => 'Ver planes de tratamiento',
            self::TreatmentPlansCreate => 'Crear planes de tratamiento',
            self::TreatmentPlansUpdateClinical => 'Actualizar clinica de planes',
            self::TreatmentPlansUpdatePricing => 'Actualizar precios de planes',
            self::TreatmentPlansApprove => 'Aprobar planes de tratamiento',
            self::TreatmentPlansSend => 'Enviar planes de tratamiento',
            self::TreatmentPlansDelete => 'Eliminar planes de tratamiento',
            // Consentimientos informados
            self::InformedConsentsView => 'Ver consentimientos informados',
            self::InformedConsentsManage => 'Gestionar consentimientos informados',
            self::InformedConsentTemplatesView => 'Ver plantillas de consentimiento',
            self::InformedConsentTemplatesCreate => 'Crear plantillas de consentimiento',
            self::InformedConsentTemplatesUpdate => 'Actualizar plantillas de consentimiento',
            self::InformedConsentTemplatesApprove => 'Aprobar plantillas de consentimiento',
            self::InformedConsentTemplatesArchive => 'Archivar plantillas de consentimiento',
            self::InformedConsentRequirementsManage => 'Gestionar requisitos de consentimiento',
            self::InformedConsentSign => 'Firmar consentimiento',
            self::InformedConsentReject => 'Rechazar consentimiento',
            self::InformedConsentRevoke => 'Revocar consentimiento',
            self::InformedConsentWaive => 'Dispensar consentimiento',
            // Consultas externas y autorizaciones
            self::ExternalClearanceView => 'Ver autorizaciones externas',
            self::ExternalClearanceManage => 'Gestionar autorizaciones externas',
            self::ExternalConsultationsView => 'Ver consultas externas',
            self::ExternalConsultationsCreate => 'Crear consultas externas',
            self::ExternalConsultationsUpdate => 'Actualizar consultas externas',
            self::ExternalConsultationsSend => 'Enviar consultas externas',
            self::ExternalConsultationsClose => 'Cerrar consultas externas',
            self::MedicalClearanceDecisionsCreate => 'Crear decisiones de autorizacion',
            self::MedicalClearanceDecisionsReview => 'Revisar decisiones de autorizacion',
            self::MedicalClearanceConditionsVerify => 'Verificar condiciones de autorizacion',
            self::ClearanceRequirementsManage => 'Gestionar requisitos de autorizacion',
            // Atencion urgente
            self::UrgentCareView => 'Ver atencion urgente',
            self::UrgentCareCreate => 'Crear registro urgente',
            self::UrgentCareUpdate => 'Actualizar registro urgente',
            self::UrgentCareDispose => 'Dispositionar paciente urgente',
            // Laboratorio dental
            self::LaboratoryCasesView => 'Ver casos de laboratorio',
            self::LaboratoryCasesCreate => 'Crear casos de laboratorio',
            self::LaboratoryCasesUpdate => 'Actualizar casos de laboratorio',
            self::LaboratoryCasesManageItems => 'Gestionar items de laboratorio',
            self::LaboratoryCasesManageEvents => 'Gestionar eventos de laboratorio',
            self::LaboratoryCasesManageDocuments => 'Gestionar documentos de laboratorio',
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
            // Casos clinicos
            self::ClinicalCasesView,
            self::ClinicalCasesCreate,
            self::ClinicalCasesUpdate,
            self::ClinicalCasesClose => 'Casos clinicos',
            // Encuentros clinicos
            self::ClinicalEncountersView,
            self::ClinicalEncountersCreate,
            self::ClinicalEncountersSign,
            self::ClinicalEncountersAmend => 'Encuentros clinicos',
            // Procedimientos realizados
            self::PerformedProceduresView,
            self::PerformedProceduresRecord,
            self::PerformedProceduresCorrect => 'Procedimientos realizados',
            // Enmiendas clinicas
            self::ClinicalAmendmentsView,
            self::ClinicalAmendmentsCreate,
            self::ClinicalAmendmentsApprove => 'Enmiendas clinicas',
            // Odontograma
            self::OdontogramsView,
            self::OdontogramsCreate,
            self::OdontogramsUpdateDraft,
            self::OdontogramsSign,
            self::OdontogramsAmend,
            self::OdontogramsAddFinding,
            self::OdontogramsAddDiagnosis,
            self::OdontogramsPlanTreatment,
            self::OdontogramsViewHistory => 'Odontograma',
            // Planes de tratamiento
            self::TreatmentPlansView,
            self::TreatmentPlansCreate,
            self::TreatmentPlansUpdateClinical,
            self::TreatmentPlansUpdatePricing,
            self::TreatmentPlansApprove,
            self::TreatmentPlansSend,
            self::TreatmentPlansDelete => 'Planes de tratamiento',
            // Consentimientos informados
            self::InformedConsentsView,
            self::InformedConsentsManage,
            self::InformedConsentTemplatesView,
            self::InformedConsentTemplatesCreate,
            self::InformedConsentTemplatesUpdate,
            self::InformedConsentTemplatesApprove,
            self::InformedConsentTemplatesArchive,
            self::InformedConsentRequirementsManage,
            self::InformedConsentSign,
            self::InformedConsentReject,
            self::InformedConsentRevoke,
            self::InformedConsentWaive => 'Consentimientos informados',
            // Consultas externas y autorizaciones
            self::ExternalClearanceView,
            self::ExternalClearanceManage,
            self::ExternalConsultationsView,
            self::ExternalConsultationsCreate,
            self::ExternalConsultationsUpdate,
            self::ExternalConsultationsSend,
            self::ExternalConsultationsClose,
            self::MedicalClearanceDecisionsCreate,
            self::MedicalClearanceDecisionsReview,
            self::MedicalClearanceConditionsVerify,
            self::ClearanceRequirementsManage => 'Consultas externas y autorizaciones',
            // Atencion urgente
            self::UrgentCareView,
            self::UrgentCareCreate,
            self::UrgentCareUpdate,
            self::UrgentCareDispose => 'Atencion urgente',
            // Laboratorio dental
            self::LaboratoryCasesView,
            self::LaboratoryCasesCreate,
            self::LaboratoryCasesUpdate,
            self::LaboratoryCasesManageItems,
            self::LaboratoryCasesManageEvents,
            self::LaboratoryCasesManageDocuments => 'Laboratorio dental',
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
