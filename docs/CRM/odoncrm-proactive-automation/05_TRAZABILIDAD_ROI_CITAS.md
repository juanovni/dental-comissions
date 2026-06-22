# Fase 5 - Trazabilidad ROI De Citas

## Objetivo

Medir que contenido social genero cada cita y luego cada procedimiento o ingreso interno.

La trazabilidad debe responder:

```text
Que post, comentario, Smart Link, evento y conversacion genero esta cita?
```

## Cadena De Atribucion

```text
SocialPost
-> SocialComment
-> SocialIdentity
-> SocialLinkEvent
-> WhatsappMessage
-> Appointment
-> Patient
-> ActivityRecord
```

## Estado Actual

`ActivityRecord` ya guarda:

```text
social_comment_id
social_identity_id
social_post_id
social_attributed_at
```

`SocialRoiService::attributeActivity()` ya puede atribuir procedimientos a un origen social cuando existe identidad/paciente vinculado.

## Cambio Propuesto

Agregar `Appointment` como puente entre WhatsApp y procedimiento.

Campos necesarios en `appointments`:

```text
social_comment_id
social_identity_id
social_post_id
patient_id
procedure_id
scheduled_at
status
source
```

## Metricas Nuevas

### Lead A Cita

```text
appointments_count / social_leads_count
```

### Smart Link A Cita

```text
appointments_count / smart_link_visits
```

### WhatsApp A Cita

```text
appointments_count / whatsapp_started_count
```

### Cita A Procedimiento

```text
activity_records_count / completed_appointments_count
```

### Revenue Por Post

Ya existe parcialmente con `ActivityRecord.social_post_id`. Debe complementarse con citas pendientes y citas completadas.

## Nuevos Reportes Recomendados

1. Posts que generaron mas citas.
2. Tratamientos con mas intencion pero baja conversion.
3. Leads con cita pendiente sin procedimiento registrado.
4. Citas creadas desde WhatsApp IA.
5. Citas perdidas por falta de seguimiento.
6. Tiempo promedio desde comentario hasta cita.
7. Tiempo promedio desde Smart Link hasta WhatsApp.
8. Tiempo promedio desde WhatsApp hasta cita.

## Actualizacion De SocialRoiService

Extender el servicio para incluir:

```text
appointmentsQuery()
appointmentSummary()
postAppointmentPerformance()
appointmentLeakageQuery()
```

## Fuga Comercial

Casos de leakage:

1. Lead con `recent_engagement_score` alto sin WhatsApp.
2. Lead con WhatsApp iniciado sin cita.
3. Lead con cita pendiente sin confirmacion.
4. Cita confirmada sin procedimiento registrado.
5. Cita no asistida sin seguimiento.

## Acciones De Auditoria

Registrar en `social_comment_actions`:

```text
appointment_created
appointment_confirmed
appointment_cancelled
appointment_no_show
appointment_completed
```

Si no se desea agregar enum inmediatamente, usar acciones existentes con `external_response.source = appointments` como transicion inicial.

## Tests Sugeridos

1. Cita creada desde lead mantiene `social_post_id`.
2. Procedimiento posterior hereda atribucion desde cita/paciente.
3. Reporte cuenta citas por post.
4. Leakage detecta citas pendientes vencidas.
5. Cita completada actualiza metricas de ROI.
