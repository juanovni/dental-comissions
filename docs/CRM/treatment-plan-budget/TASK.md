# TASK Plan de Tratamiento y Presupuesto

## Estado general

Objetivo: implementar el modulo clinico-comercial de planes de tratamiento, presupuestos, consentimientos informados, consultas externas y flujo seguro en OdonCRM.

Documentos base:
- `docs/CRM/treatment-plan-budget/00_PROPUESTA.md`
- `docs/CRM/treatment-plan-budget/01_MODELO_DATOS.md`
- `docs/CRM/treatment-plan-budget/02_FLUJOS_Y_REGLAS.md`
- `docs/CRM/treatment-plan-budget/03_IMPLEMENTACION_Y_CALIDAD.md`
- `docs/CRM/treatment-plan-budget/04_CASOS_CLINICOS_ESPECIALIZADOS.md`
- `docs/CRM/treatment-plan-budget/05_ODONTOGRAMA.md`
- `docs/CRM/treatment-plan-budget/06_MAPA_MODULOS_ODONCRM.md`
- `docs/CRM/treatment-plan-budget/07_VALIDACION_FLUJO_CLINICO.md`
- `docs/CRM/treatment-plan-budget/08_CATALOGOS_REGIONALES_Y_FISCALIDAD.md`
- `docs/CRM/treatment-plan-budget/09_ONBOARDING_TENANT.md`

Estados:
- `[ ]` Pendiente
- `[~]` En progreso
- `[x]` Completado
- `[!]` Bloqueado o requiere decision

---

## Bloque 1: Fundacion y seguridad clinica `[x]`

### Enums
- [x] `ProfessionalRole`, `CommissionType`, `ActivityStatus`, `WeeklyReportStatus`, `WhatsappMessageStatus`, `WhatsappMessageDirection`
- [x] `ClinicalPriority`, `ClinicalAlertLevel`, `ClinicalAlertSource`, `ReadinessStatus`, `ReadinessMoment`, `IdentityStatus`, `MedicalProfileVersionStatus`

### Migraciones
- [x] `specialties`
- [x] `patient_medical_profiles`
- [x] `patient_medical_profile_versions`
- [x] `patient_allergies`
- [x] `patient_medications`
- [x] `patient_medical_conditions`
- [x] `patient_clinical_alerts`
- [x] `medical_profile_reviews`
- [x] `clinical_safety_rule_sets`
- [x] `clinical_safety_rule_set_versions`
- [x] `clinical_safety_rules`
- [x] `clinical_safety_rule_applications`
- [x] `clinical_readiness_evaluations`
- [x] Campos en `patients`: `identity_status`, `medical_profile_id`
- [x] Campos en `procedures`: `commercial_price`, `specialty_id`, `requires_clinical_case`, `clinical_case_type`
- [x] Campos en `professionals`: `specialty_id`, `license_number`, `digital_signature`
- [x] RLS en todas las tablas clinicas

### Modelos (17)
- [x] `Specialty`, `PatientMedicalProfile`, `PatientMedicalProfileVersion`, `PatientAllergy`, `PatientMedication`, `PatientMedicalCondition`, `PatientClinicalAlert`, `MedicalProfileReview`, `ClinicalSafetyRuleSet`, `ClinicalSafetyRuleSetVersion`, `ClinicalSafetyRule`, `ClinicalSafetyRuleApplication`, `ClinicalReadinessEvaluation`

### Permisos
- [x] `medical_profiles.*`, `clinical_alerts.*`, `safety_rules.*`, `specialties.*`

---

## Bloque 2: Planes de tratamiento y odontograma `[x]`

### Enums
- [x] `TreatmentPlanLifecycleStatus`, `TreatmentPlanCommercialStatus`, `TreatmentPlanClinicalStatus`
- [x] `TreatmentPlanItemCommercialStatus`, `TreatmentPlanItemClinicalStatus`, `TreatmentPlanRevisionStatus`
- [x] `DependencyType`, `PhaseStatus`
- [x] `OdontogramEntryType`, `OdontogramEntryStatus`, `OdontogramSurface`, `OdontogramTargetType`, `OdontogramEventType`, `OdontogramDentitionType`

### Migraciones
- [x] `treatment_plans`
- [x] `treatment_plan_revisions`
- [x] `treatment_plan_phases`
- [x] `treatment_plan_alternative_groups`
- [x] `treatment_plan_items`
- [x] `treatment_plan_item_dependencies`
- [x] `treatment_plan_revision_items`
- [x] `treatment_plan_events`
- [x] `treatment_plan_item_responses`
- [x] `treatment_plan_public_links`
- [x] `treatment_plan_item_appointments`
- [x] `odontograms`
- [x] `odontogram_entries`
- [x] `odontogram_entry_surfaces`
- [x] `odontogram_entry_targets`
- [x] `odontogram_events`
- [x] `odontogram_condition_catalog`

### Modelos (17)
- [x] `TreatmentPlan`, `TreatmentPlanRevision`, `TreatmentPlanPhase`, `TreatmentPlanAlternativeGroup`, `TreatmentPlanItem`, `TreatmentPlanItemDependency`, `TreatmentPlanRevisionItem`, `TreatmentPlanEvent`, `TreatmentPlanItemResponse`, `TreatmentPlanPublicLink`, `TreatmentPlanItemAppointment`
- [x] `Odontogram`, `OdontogramEntry`, `OdontogramEntrySurface`, `OdontogramEntryTarget`, `OdontogramEvent`, `OdontogramConditionCatalog`

### Filament Resources
- [x] `TreatmentPlanResource` (form con Repeater de items, estados, precios)

### Permisos
- [x] `treatment_plans.*`, `odontograms.*`

---

## Bloque 3: Encuentros clinicos y procedimientos realizados `[x]`

### Enums
- [x] `ClinicalCaseStatus`, `ClinicalCaseType`, `EncounterStatus`, `EncounterType`, `CarePath`, `PerformedProcedureStatus`

### Migraciones
- [x] `clinical_cases`
- [x] `clinical_encounters`
- [x] `performed_procedures`
- [x] `clinical_record_amendments`

### Modelos (4)
- [x] `ClinicalCase`, `ClinicalEncounter`, `PerformedProcedure`, `ClinicalRecordAmendment`

### Filament Resources
- [x] `ClinicalCaseResource`
- [x] `ClinicalEncounterResource` (form SOAP)
- [x] `PerformedProcedureResource`

### Permisos
- [x] `clinical_cases.*`, `clinical_encounters.*`, `performed_procedures.*`, `clinical_amendments.*`

---

## Bloque 4: Consentimientos informados `[x]`

### Enums
- [x] `ConsentTemplateType`, `ConsentTemplateStatus`, `ConsentRequirementStatus`, `ConsentDecisionStatus`, `ConsentEventType`, `ConsentScope`

### Migraciones
- [x] `informed_consent_templates`
- [x] `informed_consent_template_versions`
- [x] `treatment_plan_item_consent_requirements`
- [x] `treatment_plan_item_consents`
- [x] `informed_consent_events`
- [x] FKs circulares y RLS

### Modelos (5)
- [x] `InformedConsentTemplate`, `InformedConsentTemplateVersion`, `TreatmentPlanItemConsentRequirement`, `TreatmentPlanItemConsent`, `InformedConsentEvent`

### Filament Resources
- [x] `InformedConsentTemplateResource`

### Permisos
- [x] `informed_consent_templates.*`, `informed_consent_requirements.*`, `informed_consents.sign/reject/revoke/waive`

---

## Bloque 5: Consultas medicas externas e interconsultas `[x]`

### Enums
- [x] `ExternalConsultationStatus`, `MedicalClearanceDecision`, `ClearanceConditionStatus`, `ClearanceRequirementType`

### Migraciones
- [x] `external_medical_consultations`
- [x] `medical_clearance_decisions`
- [x] `medical_clearance_conditions`
- [x] `treatment_plan_item_clearance_requirements`
- [x] FKs circulares y RLS

### Modelos (4)
- [x] `ExternalMedicalConsultation`, `MedicalClearanceDecision`, `MedicalClearanceCondition`, `TreatmentPlanItemClearanceRequirement`

### Filament Resources
- [x] `ExternalConsultationResource` (con Repeater de preguntas)

### Permisos
- [x] `external_consultations.*`, `medical_clearance_decisions.*`, `medical_clearance_conditions.*`, `clearance_requirements.*`

---

## Bloque 6: Atencion urgente y laboratorio dental `[x]`

### Enums
- [x] `UrgentCareArrivalMode` (walk_in, ambulance, transfer, self_referral)
- [x] `UrgentCareTriageCategory` (immediate, urgent, semi_urgent, non_urgent)
- [x] `UrgentCareDisposition` (discharged, admitted, transferred, deferred)
- [x] `LaboratoryCaseStatus` (draft, ordered, sent, accepted_by_lab, in_production, received, quality_review, ready_for_patient, adjustment_required, cancelled)
- [x] `LaboratoryCaseEventType` (ordered, sent, received, quality_review, adjustment, remake, cancelled, communicated)
- [x] `LaboratoryCaseItemStatus` (pending, in_production, received, approved, adjustment_required)

### Migraciones
- [x] `urgent_care_intakes` (paciente, encuentro, modo llegada, triage, banderas rojas, tamizaje, disposition, plazos)
- [x] `dental_laboratory_cases` (paciente, laboratorio, profesional, estado, fechas prometidas)
- [x] `dental_laboratory_case_items` (item del plan, pieza, trabajo, material, color, especificaciones)
- [x] `dental_laboratory_events` (orden, envio, recepcion, control calidad, ajuste, remake, cancelacion)
- [x] `dental_laboratory_documents` (prescripcion, escaneo, fotografia, archivo, guia, resultado en storage privado)
- [x] RLS en todas las tablas

### Modelos
- [x] `UrgentCareIntake`
- [x] `DentalLaboratoryCase`, `DentalLaboratoryCaseItem`, `DentalLaboratoryEvent`, `DentalLaboratoryDocument`

### Filament Resources
- [x] `UrgentCareIntakeResource` (form con triage, disposition, banderas rojas)
- [x] `DentalLaboratoryCaseResource` (form con Repeater de items, instrucciones)

### Permisos
- [x] `urgent_care.*` (view, create, update, dispose)
- [x] `laboratory_cases.*` (view, create, update, manage_items, manage_events, manage_documents)

---

## Bloque 7: Recall clinico y seguimiento `[ ]`

### Enums pendientes
- [ ] `RecallType` (general, periodontal, implant, retention, custom)
- [ ] `RecallPolicyStatus` (draft, active, archived)
- [ ] `PatientRiskLevel` (low, moderate, high, very_high)
- [ ] `FollowUpChannel` (whatsapp, phone, email, sms, in_person)
- [ ] `FollowUpStatus` (pending, performed, failed, cancelled)
- [ ] `FollowUpOutcome` (contacted, no_answer, rescheduled, completed, declined)

### Migraciones pendientes
- [ ] `recall_types`
- [ ] `recall_policy_versions`
- [ ] `patient_risk_assessments`
- [ ] `patient_recall_plans`
- [ ] `patient_recall_events`
- [ ] `treatment_plan_follow_ups`

### Modelos pendientes
- [ ] `RecallType`, `RecallPolicyVersion`, `PatientRiskAssessment`, `PatientRecallPlan`, `PatientRecallEvent`
- [ ] `TreatmentPlanFollowUp`

### Filament Resources pendientes
- [ ] `RecallTypeResource`
- [ ] `PatientRecallPlanResource`

### Permisos pendientes
- [ ] `clinical_recall.*`, `follow_ups.*`

---

## Bloque 8: Enlaces publicos y experiencia del paciente `[ ]`

### Funcionalidad pendiente
- [ ] Auditar `treatment_plan_public_links` existente
- [ ] Control de acceso por token con expiracion
- [ ] Vista publica del plan con aceptacion/rechazo parcial
- [ ] Generacion de PDF del presupuesto (barryvdh/laravel-dompdf)
- [ ] Registro de eventos de visualizacion (first_viewed, last_viewed)
- [ ] Revocacion de enlaces
- [ ] Endpoint publico para aceptacion parcial
- [ ] Registro de solicitud de contacto y agendamiento

### Permisos pendientes
- [ ] `public_links.*`

---

## Bloque 9: Reglas de secuencia predeterminadas `[ ]`

### Enums pendientes
- [ ] `SequenceRuleSetStatus` (draft, active, archived)
- [ ] `SequenceRuleAction` (must_complete_before, must_start_before, requires_result, minimum_healing_interval)
- [ ] `SequenceOverrideStatus` (active, superseded)

### Migraciones pendientes
- [ ] `clinical_sequence_rule_sets`
- [ ] `clinical_sequence_rule_set_versions`
- [ ] `clinical_sequence_rules`
- [ ] `treatment_plan_sequence_rule_applications`
- [ ] `treatment_plan_sequence_overrides`

### Modelos pendientes
- [ ] `ClinicalSequenceRuleSet`, `ClinicalSequenceRuleSetVersion`, `ClinicalSequenceRule`, `TreatmentPlanSequenceRuleApplication`, `TreatmentPlanSequenceOverride`

---

## Bloque 10: Citas planificadas `[ ]`

### Enums pendientes
- [ ] `PlannedAppointmentStatus` (draft, confirmed, converted, cancelled)
- [ ] `PlannedAppointmentItemStatus` (pending, partially_completed, completed, cancelled)

### Migraciones pendientes
- [ ] `planned_appointments`
- [ ] `planned_appointment_items`

### Modelos pendientes
- [ ] `PlannedAppointment`, `PlannedAppointmentItem`

### Funcionalidad pendiente
- [ ] Agrupacion clinica previa a reservar fecha
- [ ] Conversion atomica a cita real con validacion de disponibilidad
- [ ] Revalidacion de preparacion clinica durante conversion
- [ ] Bloqueo de doble conversion

---

## Bloque 11: Trazabilidad de dispositivos criticos `[ ]`

### Enums pendientes
- [ ] `TraceableProductType` (implant, graft, membrane, prosthetic, custom)
- [ ] `LotMovementType` (received, reserved, released, used, discarded, adjusted)
- [ ] `SubstitutionPolicy` (exact, equivalent, upgraded, manual)

### Migraciones pendientes
- [ ] `traceable_clinical_products`
- [ ] `traceable_product_lots`
- [ ] `traceable_product_lot_movements`
- [ ] `treatment_plan_item_traceable_product_requirements`
- [ ] `performed_procedure_material_usages`

### Modelos pendientes
- [ ] `TraceableClinicalProduct`, `TraceableProductLot`, `TraceableProductLotMovement`, `TreatmentPlanItemTraceableProductRequirement`, `PerformedProcedureMaterialUsage`

### Funcionalidad pendiente
- [ ] Ledger minimo: disponibilidad, vencimiento, recall
- [ ] Validar lote/serial antes de ejecutar y al completar
- [ ] Bloquear reutilizacion de serial o doble consumo

---

## Bloque 12: Recuperacion inteligente de agenda `[ ]`

### Enums pendientes
- [ ] `RecoveryCandidateStatus` (detected, offered, accepted, declined, expired)
- [ ] `RecoveryOfferStatus` (pending, sent, accepted, declined, expired)

### Migraciones pendientes
- [ ] `schedule_gaps`
- [ ] `recovery_candidates`
- [ ] `recovery_offers`
- [ ] `recovery_offer_attempts`

### Modelos pendientes
- [ ] `ScheduleGap`, `RecoveryCandidate`, `RecoveryOffer`, `RecoveryOfferAttempt`

### Funcionalidad pendiente
- [ ] Detectar espacios liberados por cancelacion/reprogramacion
- [ ] Buscar items aceptados sin cita compatibles
- [ ] Comparar duracion, profesional, especialidad, prioridad
- [ ] Puntaje explicable con razones
- [ ] Seleccion de contacto y registro de resultado

---

## Bloque 13: Gestion financiera `[ ]`

### Enums pendientes
- [ ] `PaymentStatus` (pending, partial, completed, refunded)
- [ ] `PaymentMethod` (cash, card, transfer, insurance, other)
- [ ] `RefundStatus` (pending, approved, completed, rejected)

### Migraciones pendientes
- [ ] `treatment_plan_payments`
- [ ] `treatment_plan_payment_allocations`
- [ ] `treatment_plan_payment_schedules`
- [ ] `treatment_plan_refunds`

### Modelos pendientes
- [ ] `TreatmentPlanPayment`, `TreatmentPlanPaymentAllocation`, `TreatmentPlanPaymentSchedule`, `TreatmentPlanRefund`

### Funcionalidad pendiente
- [ ] Pagos, abonos, saldos
- [ ] Caja y gastos
- [ ] Cuotas y calendarios de pago
- [ ] Devoluciones y conciliacion

---

## Bloque 14: Disponibilidad profesional `[ ]`

### Migraciones pendientes
- [ ] `professional_schedules`
- [ ] `professional_schedule_exceptions`
- [ ] `professional_time_blocks`

### Modelos pendientes
- [ ] `ProfessionalSchedule`, `ProfessionalScheduleException`, `ProfessionalTimeBlock`

### Funcionalidad pendiente
- [ ] Horarios base por profesional
- [ ] Excepciones y bloqueos
- [ ] Integracion con disponibilidad de citas

---

## Bloque 15: Preferencias del paciente `[ ]`

### Migraciones pendientes
- [ ] `patient_contact_preferences`
- [ ] `patient_contact_consents`
- [ ] `patient_availability_preferences`

### Modelos pendientes
- [ ] `PatientContactPreference`, `PatientContactConsent`, `PatientAvailabilityPreference`

### Funcionalidad pendiente
- [ ] Canales preferidos de contacto
- [ ] Consentimiento de comunicaciones
- [ ] Preferencias de disponibilidad horaria

---

## Bloque 16: Extensiones clinicas especializadas `[ ]`

### Migraciones pendientes
- [ ] `orthodontic_cases`
- [ ] `orthodontic_controls`
- [ ] `endodontic_cases`
- [ ] `endodontic_canals`
- [ ] `endodontic_sessions`
- [ ] `periodontal_cases`
- [ ] `periodontal_exams`
- [ ] `periodontal_measurements`
- [ ] `clinical_documents`

### Modelos pendientes
- [ ] `OrthodonticCase`, `OrthodonticControl`, `EndodonticCase`, `EndodonticCanal`, `EndodonticSession`, `PeriodontalCase`, `PeriodontalExam`, `PeriodontalMeasurement`, `ClinicalDocument`

### Funcionalidad pendiente
- [ ] Formularios especializados por tipo de caso
- [ ] Evolucion longitudinal por especialidad
- [ ] Documentos y evidencia clinica

---

## Estadisticas

| Bloque | Estado | Enums | Migraciones | Models | Resources | Permisos |
|--------|--------|-------|-------------|--------|-----------|----------|
| 1 | [x] | 14 | 17 | 13 | 0 | 4 |
| 2 | [x] | 14 | 17 | 17 | 1 | 2 |
| 3 | [x] | 6 | 4 | 4 | 3 | 4 |
| 4 | [x] | 6 | 7 | 5 | 1 | 4 |
| 5 | [x] | 4 | 6 | 4 | 1 | 4 |
| 6 | [x] | 6 | 6 | 5 | 2 | 2 |
| 7 | [ ] | 6 | 6 | 7 | 2 | 2 |
| 8 | [ ] | 0 | 0 | 0 | 0 | 1 |
| 9 | [ ] | 3 | 5 | 5 | 0 | 0 |
| 10 | [ ] | 2 | 2 | 2 | 0 | 0 |
| 11 | [ ] | 3 | 5 | 5 | 0 | 0 |
| 12 | [ ] | 2 | 4 | 4 | 0 | 0 |
| 13 | [ ] | 3 | 4 | 4 | 0 | 0 |
| 14 | [ ] | 0 | 3 | 3 | 0 | 0 |
| 15 | [ ] | 0 | 3 | 3 | 0 | 0 |
| 16 | [ ] | 0 | 9 | 9 | 0 | 0 |

**Bloques completados:** 6/16
**Migraciones creadas:** 57 (000001 - 000054 + RLS + FKs)
**Migraciones pendientes:** ~59
