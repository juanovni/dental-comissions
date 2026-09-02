# Modelo de Datos - Plan de Tratamiento y Presupuesto

## Objetivo Del Modelo

Representar de forma separada y trazable:

1. La intencion clinica del plan.
2. La version economica presentada al paciente.
3. La decision comercial por procedimiento.
4. La programacion y ejecucion clinica.
5. El historial de cambios y acciones.

Todas las tablas del modulo deben incluir `clinic_id`, usar `BelongsToTenant`, tener RLS y validar que sus relaciones pertenezcan a la misma clinica.

## Perfil Medico Y Seguridad Clinica

La seguridad clinica no debe depender de `patients.notes` ni de metadata libre. Se requieren registros estructurados y versionados:

- `patient_medical_profiles`: contenedor vigente del paciente.
- `patient_medical_profile_versions`: respuestas inmutables, procedencia, informante, fecha efectiva y estado de revision.
- `patient_allergies`: sustancia, reaccion, severidad, estado y fuente.
- `patient_medications`: medicamento, principio activo cuando se conozca, dosis, frecuencia, indicacion y vigencia.
- `patient_medical_conditions`: condicion, estado, control, profesional tratante y fechas relevantes.
- `patient_clinical_alerts`: alerta manual o derivada, alcance, nivel de bloqueo, fuente y vigencia.
- `medical_profile_reviews`: profesional, version revisada, resultado, cambios reconocidos y proxima revision.
- `clinical_safety_rule_sets`: conjunto de reglas aprobado para una clinica o paquete revisado.
- `clinical_safety_rule_set_versions`: contenido inmutable, fuente, responsable y vigencia.
- `clinical_safety_rules`: combinacion de condicion, medicamento, alergia, procedimiento o tecnica y accion resultante.
- `clinical_safety_rule_applications`: regla evaluada, contexto, resultado y evidencia para un paciente e item.
- `clinical_readiness_evaluations`: resultado para agendamiento o ejecucion y evidencias exactas utilizadas.

Niveles de control:

- `advisory`: informa y puede exigir reconocimiento, pero no bloquea.
- `conditional_hold`: bloquea hasta resolver o hasta que un profesional autorizado registre una excepcion con motivo, alcance y vencimiento.
- `hard_stop`: no admite override operativo ordinario; se resuelve actualizando evidencia, requisito o decision clinica definida.

La severidad clinica y el nivel de bloqueo son conceptos diferentes. Las reglas deben registrar fuente, version, fecha de revision y responsable. El sistema no debe presentarse como un verificador exhaustivo de interacciones farmacologicas sin una base clinica validada y mantenida.

### Interconsultas Y Autorizaciones Medicas Externas

- `external_medical_consultations`: paciente, profesional solicitante, destinatario, especialidad, motivo, preguntas, estado y fechas de envio/recepcion.
- `medical_clearance_decisions`: respuesta revisada, decision, condiciones, alcance, vigencia, emisor, documento y profesional verificador.
- `medical_clearance_conditions`: decision, condicion verificable, estado, evidencia, verificador y fecha de cumplimiento.
- `treatment_plan_item_clearance_requirements`: item afectado, tipo de requisito, momento requerido (`scheduling`, `execution` o ambos), estado y decision que lo satisface.

Una respuesta recibida no equivale automaticamente a autorizacion. Solo una decision revisada por un profesional autorizado, vigente y aplicable al item puede satisfacer el requisito. Los documentos se almacenan como documentos clinicos privados.

Estados de interconsulta sugeridos: `draft`, `requested`, `sent`, `received`, `under_review`, `closed` y `cancelled`. Decisiones sugeridas: `cleared`, `cleared_with_conditions`, `not_cleared`, `insufficient_information`, `expired` y `superseded`. Una decision condicionada solo satisface preparacion cuando todas sus condiciones obligatorias tienen evidencia verificada.

## Entidades Principales

### `treatment_plans`

Cabecera estable del plan:

```text
id
clinic_id
patient_id
responsible_professional_id
source_appointment_id nullable
current_revision_id nullable
code
title
diagnosis_summary nullable
clinical_priority
lifecycle_status
commercial_status
clinical_status
currency
valid_until nullable
created_by
approved_by nullable
sent_at nullable
accepted_at nullable
rejected_at nullable
completed_at nullable
cancelled_at nullable
created_at
updated_at
```

Reglas:

- `code` debe ser unico por clinica.
- `clinic_id` es inmutable.
- El paciente, profesional y cita de origen deben pertenecer a la misma clinica.
- El total visible se obtiene de la revision vigente, no de campos editables del plan.
- Los planes enviados no se editan directamente; se crea una nueva revision.

### `treatment_plan_revisions`

Snapshot inmutable de cada version emitida:

```text
id
clinic_id
treatment_plan_id
revision_number
status
currency
subtotal
discount_total
tax_total
total
payment_terms nullable
patient_notes nullable
internal_change_reason nullable
valid_until nullable
content_checksum
created_by
approved_by nullable
approved_at nullable
issued_at nullable
created_at
updated_at
```

Reglas:

- `revision_number` es unico dentro del plan.
- Una revision emitida, visualizada o aceptada es inmutable.
- Cualquier modificacion crea una nueva revision.
- `content_checksum` permite verificar la integridad de lo aceptado.
- Los importes deben ser no negativos y respetar la moneda del plan.

### `treatment_plan_items`

Procedimientos clinicos estables del plan:

```text
id
clinic_id
treatment_plan_id
procedure_id nullable
phase_id nullable
alternative_group_id nullable
tooth nullable
surface nullable
quantity
duration_minutes
sessions
specialty_id nullable
suggested_professional_id nullable
clinical_priority
sequence_order
readiness_status
clinical_hold_reason nullable
recommended_from nullable
recommended_until nullable
commercial_status
clinical_status
rejection_reason nullable
display_order
created_at
updated_at
```

Reglas:

- El item representa la intencion clinica a traves de las revisiones.
- El procedimiento del catalogo es una referencia y debe pertenecer a la misma clinica.
- `quantity`, `duration_minutes` y `sessions` deben ser mayores que cero.
- El profesional sugerido debe pertenecer a la clinica.
- Estado comercial y clinico nunca deben mezclarse.
- La aceptacion no vuelve al item automaticamente listo para agendar.
- `readiness_status` depende de prerequisitos, consentimientos, alertas y bloqueos clinicos.
- La UI puede proyectar `requires_informed_consent` desde requisitos activos, pero no debe persistirlo como fuente autoritativa.

### `treatment_plan_phases`

```text
id
clinic_id
treatment_plan_id
name
sequence_order
status
notes nullable
created_at
updated_at
```

### `treatment_plan_item_dependencies`

```text
id
clinic_id
treatment_plan_item_id
depends_on_item_id
dependency_type
minimum_interval_days nullable
notes nullable
created_at
```

Tipos sugeridos:

- `must_complete_before`
- `must_start_before`
- `requires_result`
- `minimum_healing_interval`

Esta relacion representa dependencias entre items del mismo plan. Una autorizacion medica externa utiliza `treatment_plan_item_clearance_requirements`, no un item artificial.

### Reglas Clinicas Predeterminadas De Secuenciacion

- `clinical_sequence_rule_sets`: conjunto publicable por clinica o paquete regional revisado.
- `clinical_sequence_rule_set_versions`: version inmutable, vigencia y responsable de aprobacion.
- `clinical_sequence_rules`: fase, precedencia, resultado, intervalo, autorizacion, consentimiento o etapa de laboratorio recomendada.
- `treatment_plan_sequence_rule_applications`: regla y version aplicadas al borrador, con dependencias generadas.
- `treatment_plan_sequence_overrides`: decision distinta, motivo clinico, profesional, alcance y vigencia.

Las reglas predeterminadas generan recomendaciones al construir un borrador. Las fases y dependencias del plan individual siguen siendo autoritativas. Publicar una nueva version no modifica planes existentes y toda excepcion obliga a recalcular preparacion clinica.

### `treatment_plan_alternative_groups`

Agrupa opciones mutuamente excluyentes o alternativas presentadas al paciente. Aceptar una opcion debe bloquear las alternativas incompatibles sin borrar su historial.

### `treatment_plan_revision_items`

Snapshot inmutable de cada item incluido en una revision:

```text
id
clinic_id
treatment_plan_revision_id
treatment_plan_item_id
procedure_name_snapshot
procedure_code_snapshot nullable
description_snapshot nullable
tooth_snapshot nullable
surface_snapshot nullable
quantity
unit_price
discount_type nullable
discount_value
tax_rate
subtotal
total
duration_minutes_snapshot
sessions_snapshot
clinical_priority_snapshot
display_order
created_at
```

Reglas:

- Los revision items son parte del documento inmutable emitido.
- Nombre, codigo, descripcion, precio y duracion no se consultan del catalogo al mostrar una revision historica.
- Una nueva version del procedimiento crea otra revision; nunca modifica este registro.
- La suma de los revision items debe coincidir con los totales de la revision.

### `treatment_plan_item_responses`

Evidencia de la decision del paciente sobre una revision exacta:

```text
id
clinic_id
treatment_plan_id
treatment_plan_revision_id
treatment_plan_revision_item_id
treatment_plan_public_link_id nullable
response
responded_at
source
notes nullable
ip_hash nullable
user_agent nullable
created_at
```

Respuestas sugeridas:

- `accepted`
- `rejected`
- `contact_requested`

La respuesta actual puede proyectarse en `treatment_plan_items.commercial_status`, pero la evidencia historica permanece append-only.

### Consentimientos Informados Por Item

- `informed_consent_templates`: tipo y proposito estable del consentimiento.
- `informed_consent_template_versions`: contenido, idioma, riesgos, alternativas, vigencia y checksum inmutables.
- `treatment_plan_item_consent_requirements`: item, plantilla requerida, obligatoriedad, alcance, momento requerido (`scheduling`, `execution` o ambos) y estado de aplicabilidad.
- `treatment_plan_item_consents`: requisito, version exacta, paciente o representante, profesional que explico, firma, fecha, evidencia y vigencia.
- `informed_consent_events`: firma, rechazo, revocacion, expiracion y sustitucion append-only.

Un item puede tener cero, uno o varios requisitos independientes. La preparacion exige que todos los requisitos obligatorios aplicables esten satisfechos. Una nueva revision economica no invalida automaticamente el consentimiento; se exige uno nuevo cuando cambian procedimiento, sitio, tecnica, riesgo material, plantilla aplicable o vigencia.

### `treatment_plan_events`

Historial durable y append-only:

```text
id
clinic_id
treatment_plan_id
treatment_plan_revision_id nullable
treatment_plan_item_id nullable
event_type
from_status nullable
to_status nullable
occurred_at
created_by nullable
professional_id nullable
source
notes nullable
metadata nullable
request_id nullable
ip_hash nullable
user_agent nullable
created_at
```

Fuentes sugeridas:

- `admin`
- `doctor`
- `reception`
- `patient_link`
- `whatsapp`
- `voice`
- `system`
- `automation`

Los eventos no deben eliminarse en cascada al archivar un plan.

### `treatment_plan_public_links`

Acceso externo controlado:

```text
id
clinic_id
treatment_plan_id
treatment_plan_revision_id
token_hash
token_prefix
purpose
status
expires_at
revoked_at nullable
first_viewed_at nullable
last_viewed_at nullable
accepted_at nullable
recipient_phone_hash nullable
recipient_email_hash nullable
metadata nullable
created_by
created_at
updated_at
```

Reglas:

- El token original solo se entrega al paciente.
- La base guarda un hash no reversible.
- El enlace se puede rotar y revocar sin modificar el plan.
- Los enlaces expirados o revocados no muestran informacion sensible.
- Los tokens no se escriben en logs.

### `treatment_plan_item_appointments`

Relacion muchos a muchos entre items y citas:

```text
id
clinic_id
treatment_plan_item_id
appointment_id
planned_quantity
completed_quantity
session_number nullable
status
notes nullable
created_at
updated_at
```

Reglas:

- Una cita puede cubrir varios items.
- Un item puede requerir varias citas.
- Completar la cita no completa automaticamente todos los items.
- La realizacion debe confirmarse de forma explicita.

### `planned_appointments`

Agrupacion clinica previa a reservar una fecha:

```text
id
clinic_id
patient_id
professional_id nullable
specialty_id nullable
status
duration_minutes
readiness_status
notes nullable
converted_appointment_id nullable
created_by
converted_at nullable
created_at
updated_at
```

`planned_appointment_items` relaciona la agrupacion con uno o varios items, cantidades y sesiones. La cita planificada no ocupa agenda. Su conversion revalida disponibilidad y preparacion dentro de la misma transaccion que crea `appointments`, vincula los items y registra `converted_appointment_id`. Una agrupacion convertida no puede convertirse de nuevo.

### Casos De Laboratorio Dental

- `dental_laboratory_cases`: paciente, laboratorio, profesional, estado, fechas prometidas y referencias clinicas.
- `dental_laboratory_case_items`: item del plan, pieza, trabajo, material, color, especificaciones y etapas requeridas para agendamiento y ejecucion.
- `dental_laboratory_events`: orden, envio, recepcion, control de calidad, ajuste, remake, cancelacion y comunicaciones.
- `dental_laboratory_documents`: prescripcion, escaneo, fotografia, archivo, guia y resultado en storage privado.

Estados base: `draft`, `ordered`, `sent`, `accepted_by_lab`, `in_production`, `received`, `quality_review`, `ready_for_patient`, `adjustment_required` y `cancelled`.

`received` no equivale a `ready_for_patient`. La cita de prueba o entrega puede planificarse tentativamente, pero no queda lista para ejecucion hasta satisfacer la etapa de laboratorio configurada. Costos, portal del laboratorio y logistica automatizada pertenecen a una capacidad posterior.

### `clinical_cases`

Caso longitudinal para tratamientos que requieren seguimiento especializado:

```text
id
clinic_id
patient_id
treatment_plan_item_id nullable
source_appointment_id nullable
responsible_professional_id
specialty_id nullable
case_type
status
title
diagnosis_summary nullable
started_at nullable
completed_at nullable
closed_at nullable
created_by
created_at
updated_at
```

Reglas:

- Un caso puede existir antes del presupuesto y vincularse despues a un item.
- No todo item crea un caso clinico.
- El tipo de caso determina la extension clinica disponible.
- El paciente, profesional, item y cita deben pertenecer a la misma clinica.
- Cerrar un caso no elimina sus encuentros, procedimientos ni documentos.

### `clinical_encounters`

Registro de cada atencion clinica:

```text
id
clinic_id
patient_id
clinical_case_id nullable
appointment_id nullable
professional_id
encounter_type
care_path
status
occurred_at
subjective_notes nullable
objective_findings nullable
assessment nullable
plan_notes nullable
signed_by nullable
signed_at nullable
created_at
updated_at
```

Reglas:

- Una cita puede originar un encuentro, pero no son la misma entidad.
- Un encuentro firmado no se sobrescribe; las correcciones se registran como enmiendas.
- Un encuentro puede contener varios procedimientos realizados.
- Los campos clinicos requieren permisos distintos de los datos comerciales.
- `care_path` distingue atencion planificada, urgencia y walk-in sin obligar a crear una cita o plan artificial.

### `urgent_care_intakes`

Complemento estructurado para atencion urgente o sin cita:

```text
id
clinic_id
patient_id
clinical_encounter_id
arrival_mode
chief_complaint
triage_category
red_flags
acute_medical_screen_status
disposition
deferred_completion_due_at nullable
created_by
created_at
updated_at
```

El flujo urgente puede diferir informacion no critica, pero no omite identidad razonablemente disponible, alergias y medicamentos relevantes, senales de alarma, consentimiento aplicable ni documentacion del encuentro.

### `performed_procedures`

Hecho clinico que confirma lo ejecutado:

```text
id
clinic_id
patient_id
clinical_encounter_id
appointment_id nullable
treatment_plan_item_id nullable
procedure_id nullable
procedure_name_snapshot
procedure_code_snapshot nullable
tooth nullable
surface nullable
quantity
status
performed_by
started_at nullable
completed_at nullable
clinical_notes nullable
metadata nullable
created_at
updated_at
```

Reglas:

- `Appointment.completed` no crea automaticamente un procedimiento realizado.
- El profesional confirma explicitamente que procedimiento se ejecuto.
- El snapshot conserva lo realizado aunque cambie el catalogo.
- El avance del item se calcula desde procedimientos realizados validos.
- Anulaciones y correcciones conservan historial; no eliminan el hecho original.
- Los procedimientos realizados quedan atestados por la firma del encuentro que los contiene; cambiarlos despues exige enmienda del encuentro y evento de correccion.

### Dispositivos Y Materiales Clinicamente Trazables

- `traceable_clinical_products`: producto, fabricante, referencia, tipo y politica de trazabilidad.
- `traceable_product_lots`: lote, serial o UDI cuando aplique, vencimiento, cantidad recibida, cantidad disponible, cuarentena y recall.
- `traceable_product_lot_movements`: entrada, reserva, liberacion, uso, descarte o ajuste con cantidad, actor y referencia clinica.
- `treatment_plan_item_traceable_product_requirements`: item, tipo de producto requerido, cantidad, momento requerido, reserva nullable y politica de sustitucion.
- `performed_procedure_material_usages`: procedimiento realizado, paciente, pieza o sitio, lote, cantidad, profesional y evento de correccion.

La primera entrega solo exige trazabilidad para productos configurados como criticos, por ejemplo implantes, componentes principales, injertos, membranas o dispositivos especificos del paciente. Antes de ejecutar se valida disponibilidad, vencimiento y recall; antes de completar el procedimiento o firmar el encuentro se registra el lote o serial realmente utilizado.

Este ledger minimo existe para decidir disponibilidad y evitar reutilizar un serial o consumir dos veces la misma cantidad. No incorpora compras, costos, stock de consumibles generales ni valoracion de inventario.

### `treatment_plan_follow_ups`

Seguimiento estructurado:

```text
id
clinic_id
treatment_plan_id
treatment_plan_item_id nullable
channel
reason
status
scheduled_at
performed_at nullable
performed_by nullable
outcome nullable
notes nullable
next_action_at nullable
external_reference nullable
metadata nullable
created_at
updated_at
```

Esta entidad permite medir intentos, resultados y frecuencia sin depender de notas libres.

## Recall Clinico Estratificado Por Riesgo

- `recall_types`: preventivo general, periodontal, implantes, retencion u otros tipos configurados.
- `recall_policy_versions`: reglas inmutables que relacionan riesgo y tipo de cuidado con un intervalo sugerido.
- `patient_risk_assessments`: dominio, nivel, factores, evidencia, evaluador, fecha y vigencia.
- `patient_recall_plans`: tipo, evaluacion y politica usadas, ultimo encuentro o procedimiento calificante, fecha calculada, fecha ajustada, motivo, profesional que ajusta y cita relacionada.
- `patient_recall_events`: tipo de evento, fechas anterior/nueva, actor, motivo, fuente clinica, politica y metadata auditada.

Un paciente puede tener varios recalls simultaneos. El intervalo sugerido se calcula desde la ultima evaluacion valida y la version de politica; el profesional puede ajustarlo con motivo. Informacion ausente o vencida nunca se interpreta automaticamente como riesgo bajo. Una cita por si sola no cumple el recall: se requiere evaluacion o procedimiento realizado calificante.

## Entidades De Evolucion

### Gestion financiera

- `treatment_plan_payments`
- `treatment_plan_payment_allocations`
- `treatment_plan_payment_schedules`
- `treatment_plan_refunds`

### Recuperacion de agenda

- `schedule_gaps`
- `recovery_candidates`
- `recovery_offers`
- `recovery_offer_attempts`

### Disponibilidad profesional

- `professional_schedules`
- `professional_schedule_exceptions`
- `professional_time_blocks`

### Preferencias y consentimiento

- `patient_contact_preferences`
- `patient_contact_consents`
- `patient_availability_preferences`

### Extensiones clinicas especializadas

- `orthodontic_cases`
- `orthodontic_controls`
- `endodontic_cases`
- `endodontic_canals`
- `endodontic_sessions`
- `periodontal_cases`
- `periodontal_exams`
- `periodontal_measurements`
- `clinical_documents`
- `clinical_record_amendments`

## Cambios En Entidades Existentes

### `procedures`

Campos recomendados:

```text
commercial_price
duration_minutes
default_sessions
specialty_id nullable
clinical_priority_default nullable
requires_clinical_case
clinical_case_type nullable
```

`internal_rate` debe conservar una semantica interna clara y no utilizarse como precio comercial.

### `appointments`

No debe agregarse un unico `treatment_plan_item_id`. La relacion debe permanecer en la tabla pivote porque es muchos a muchos.

Los flujos que reprograman automaticamente la ultima cita futura del paciente deben modificarse para permitir multiples citas futuras.

### `patients`

Agregar `identity_status` con valores `provisional`, `verified` y `merged`. Una identidad provisional creada en urgencias debe mostrar restricciones operativas y una fecha limite de conciliacion.

`patient_identity_merges` conserva paciente origen, paciente destino, actor, motivo, fecha y referencias transferidas. La conciliacion no elimina ni reasigna silenciosamente registros clinicos; mantiene alias y procedencia auditables.

### `social_comments`

Puede agregarse una relacion con planes originados desde el lead, pero `estimated_value` sigue siendo una estimacion comercial. Los valores reales se obtienen de las revisiones del plan.

## Estados

### Estado general del plan

El plan no debe mezclar todas sus dimensiones en un unico Enum.

Lifecycle:

- `draft`
- `pending_approval`
- `active`
- `superseded`
- `administratively_closed`
- `cancelled`

Resumen comercial derivado:

- `not_sent`
- `sent`
- `viewed`
- `partially_accepted`
- `accepted`
- `rejected`
- `expired`

Progreso clinico derivado:

- `not_started`
- `in_progress`
- `partially_completed`
- `completed`
- `on_hold`
- `unresolved_needs`

### Estado comercial del item

- `proposed`
- `accepted`
- `rejected`
- `expired`
- `withdrawn`

### Estado clinico-operativo del item

- `unscheduled`
- `scheduled`
- `in_progress`
- `completed`
- `cancelled`

### Estado del caso clinico

- `draft`
- `active`
- `on_hold`
- `completed`
- `closed`
- `cancelled`

### Estado del encuentro clinico

- `draft`
- `signed`
- `amended`
- `cancelled`

La palabra `pending` debe utilizarse como condicion derivada, no como estado ambiguo.

Ejemplos:

```text
accepted + unscheduled = pendiente de agendamiento
proposed + unscheduled = pendiente de decision
accepted + cancelled = pendiente de reprogramacion si sigue vigente
```

## Invariantes

1. Todo registro pertenece exactamente a una clinica.
2. No se aceptan relaciones entre tenants distintos.
3. Una revision emitida es inmutable.
4. Una aceptacion siempre referencia una revision exacta.
5. Los precios historicos no dependen del catalogo actual.
6. Un item rechazado no se agenda sin una nueva aceptacion.
7. Un item aceptado puede tener cero, una o varias citas.
8. Una cita completada requiere confirmacion explicita de items realizados.
9. Los estados cambian mediante servicios de dominio, no por edicion directa.
10. Toda transicion relevante crea un evento en la misma transaccion.
11. Los totales se calculan en servidor y se validan antes de emitir.
12. El valor economico nunca sustituye la prioridad clinica.
13. Un item del plan no equivale a un caso clinico.
14. Un caso clinico no equivale a una cita.
15. Solo un procedimiento realizado confirmado actualiza el avance ejecutado.
16. Los encuentros firmados y procedimientos confirmados no se sobrescriben sin trazabilidad.
17. La aceptacion economica no autoriza por si sola la ejecucion clinica.
18. Un item solo puede agendarse como listo cuando cumple prerequisitos y bloqueos.
19. Un plan puede cerrarse administrativamente y conservar necesidades clinicas no resueltas.
20. Alternativas incompatibles no pueden ejecutarse simultaneamente sin una decision clinica auditada.
21. La ausencia de alergias debe registrarse explicitamente; no se infiere por ausencia de filas.
22. Un bloqueo duro no puede ser eliminado por recepcion ni por un override ordinario.
23. Agendar y ejecutar utilizan evaluaciones de preparacion separadas y conservan las versiones de evidencia consultadas.
24. Todo consentimiento firmado referencia un item y una version exacta de plantilla.
25. Una autorizacion externa vencida, insuficiente o fuera de alcance no satisface preparacion clinica.
26. Un encuentro urgente puede existir sin cita ni plan, pero no sin el conjunto minimo de seguridad.
27. Actualizar reglas predeterminadas no reescribe dependencias de planes existentes.
28. Un trabajo de laboratorio recibido no habilita ejecucion hasta aprobar su control de calidad cuando aplique.
29. Un producto critico utilizado debe poder rastrearse desde el lote hasta el paciente y procedimiento realizado.
30. El recall conserva la evaluacion de riesgo y politica que originaron cada fecha calculada.

## Indices Y Restricciones

- Unique `(clinic_id, code)` en planes.
- Unique `(treatment_plan_id, revision_number)` en revisiones.
- Unique `(treatment_plan_revision_id, treatment_plan_item_id)` en items de revision.
- Unique `token_hash` en enlaces publicos.
- Indices por `(clinic_id, lifecycle_status, valid_until)` en planes.
- Indices por `(clinic_id, patient_id, commercial_status, clinical_status)` en planes.
- Indices por `(clinic_id, commercial_status, clinical_status)` en items.
- Indices por `(clinic_id, patient_id, case_type, status)` en casos clinicos.
- Indices por `(clinic_id, patient_id, occurred_at)` en encuentros.
- Indices por `(clinic_id, treatment_plan_item_id, status)` en procedimientos realizados.
- Checks para importes no negativos.
- Checks para cantidad, sesiones y duracion mayores que cero.
- Foreign keys y validaciones de consistencia tenant.
- Optimistic locking para edicion concurrente de borradores.

## Decisiones Pendientes

1. Definir si el impuesto se activa por configuracion de clinica.
2. Definir tipos de descuento y limites autorizables.
3. Definir si la especialidad sera entidad formal desde el inicio.
4. Definir si la aceptacion requiere OTP o solo posesion del enlace.
5. Definir cuando un plan se considera comercialmente ganado.
6. Definir politica de archivo y retencion legal.
7. Definir reglas de seleccion, reemplazo y nueva aceptacion para grupos alternativos.
8. Definir que tipos de procedimiento requieren caso clinico por defecto.
9. Definir politica de firma y enmienda de encuentros clinicos.
10. Definir retencion y acceso de fotografias, radiografias y documentos.
11. Definir el conjunto inicial de alertas clinicas validadas y sus responsables de mantenimiento.
12. Validar por pais los requisitos de consentimiento, interconsulta, retencion y firma.
13. Definir productos criticos con trazabilidad obligatoria por tipo de practica.
14. Definir politicas iniciales de riesgo y recall con responsables clinicos.
