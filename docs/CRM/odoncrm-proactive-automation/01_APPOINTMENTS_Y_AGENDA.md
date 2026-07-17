# Fase 1 - Appointments y Agenda Interna

## Objetivo

Crear una tabla propia `appointments` para registrar citas originadas por leads sociales, WhatsApp, llamadas telefonicas, Smart Links o acciones manuales del equipo.

Esta tabla debe funcionar como fuente interna de trazabilidad comercial aunque despues se integre una agenda externa.

## Por Que Una Tabla Propia

1. Mantiene control de atribucion social.
2. Evita depender desde el inicio de Google Calendar, Calendly, Doctoralia u otro proveedor.
3. Permite auditar que contenido genero cada cita.
4. Permite sincronizacion futura bidireccional.
5. Permite operar aunque falle el proveedor externo.
6. Permite reportes internos de conversion antes de tener agenda externa.

## Modelo Propuesto

Entidad: `Appointment`.

Tabla: `appointments`.

Campos principales:

```text
id
patient_id
social_comment_id
social_identity_id
social_post_id
procedure_id
assigned_user_id
scheduled_at
duration_minutes
status
source
notes
created_by
confirmed_at
cancelled_at
completed_at
no_show_at
metadata
created_at
updated_at
```

Campos para integracion externa futura:

```text
external_provider
external_appointment_id
external_calendar_id
external_status
external_payload
last_synced_at
sync_error
```

## Relaciones

1. `Appointment belongsTo Patient`.
2. `Appointment belongsTo SocialComment`.
3. `Appointment belongsTo SocialIdentity`.
4. `Appointment belongsTo SocialPost`.
5. `Appointment belongsTo Procedure`.
6. `Appointment belongsTo User` como usuario asignado.
7. `Appointment belongsTo User` como creador.

## Estados Recomendados

Enum: `AppointmentStatus`.

```text
pending_confirmation
scheduled
confirmed
rescheduled
cancelled
completed
no_show
```

Etiquetas sugeridas:

```text
pending_confirmation: Pendiente de confirmar
scheduled: Agendada
confirmed: Confirmada
rescheduled: Reprogramada
cancelled: Cancelada
completed: Completada
no_show: No asistio
```

## Fuentes Recomendadas

Enum o string parametrizable: `source`.

```text
whatsapp_ai
whatsapp_human
voice_ai
voice_human
smart_link
admin_manual
external_provider
```

## Flujo Inicial

1. Lead llega desde redes y obtiene token.
2. Lead entra a Smart Link y hace clic en WhatsApp.
3. WhatsApp recibe mensaje con token.
4. Se recupera `SocialComment` por `tracking_token`.
5. IA detecta intencion de agenda o cierre.
6. Se crea `Appointment` en estado `pending_confirmation` o `scheduled`.
7. Se registra accion en `social_comment_actions`.
8. Se actualiza `conversion_status` a `appointment_created` cuando la cita queda creada.

## Regla De Oro

La cita puede ser sugerida o creada operativamente por automatizacion, pero la decision clinica y el diagnostico siempre deben quedar en manos del equipo humano.

## Validaciones

1. No crear cita sin `scheduled_at` si el estado es `scheduled` o `confirmed`.
2. Permitir cita sin `patient_id` solo si el lead todavia esta pendiente de ficha.
3. Mantener `social_comment_id` cuando la cita venga de redes.
4. Guardar `external_payload` solo como auditoria, no como fuente primaria de negocio.
5. Indexar `scheduled_at`, `status`, `patient_id`, `social_comment_id` y `external_provider/external_appointment_id`.

## Tests Sugeridos

1. Crear cita desde lead con `social_comment_id`.
2. Crear cita sin paciente pero con `social_identity_id`.
3. Actualizar estado de cita y registrar timestamps.
4. Validar que `conversion_status` cambia a `appointment_created`.
5. Validar que una cita externa se puede vincular por `external_provider` y `external_appointment_id`.
