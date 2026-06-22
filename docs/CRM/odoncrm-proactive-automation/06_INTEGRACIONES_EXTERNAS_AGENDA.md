# Fase 6 - Integraciones Externas De Agenda

## Objetivo

Permitir que `appointments` se sincronice con una agenda externa sin perder trazabilidad interna.

La tabla local debe seguir siendo el registro CRM. La app externa puede ser el calendario operativo.

## Proveedores Posibles

```text
google_calendar
calendly
doctoralia
agenda_clinica_custom
outlook_calendar
```

## Estrategia De Integracion

### Outbound

OdonCRM crea una cita local y luego la envia al proveedor externo.

Flujo:

1. Crear `Appointment` local.
2. Enviar evento a proveedor externo.
3. Recibir ID externo.
4. Guardar `external_provider` y `external_appointment_id`.
5. Guardar respuesta completa en `external_payload`.
6. Marcar `last_synced_at`.

### Inbound

Proveedor externo crea o modifica una cita y notifica a OdonCRM por webhook.

Flujo:

1. Recibir webhook externo.
2. Buscar por `external_provider` y `external_appointment_id`.
3. Si existe, actualizar cita local.
4. Si no existe, crear cita local con `source = external_provider`.
5. Intentar resolver paciente/lead por telefono, email, metadata o token.

## Campos Externos

```text
external_provider
external_appointment_id
external_calendar_id
external_status
external_payload
last_synced_at
sync_error
```

## Servicio Propuesto

Contrato:

```text
App\Contracts\AppointmentCalendarProvider
```

Metodos:

```text
create(Appointment $appointment): AppointmentSyncResult
update(Appointment $appointment): AppointmentSyncResult
cancel(Appointment $appointment): AppointmentSyncResult
normalizeWebhook(array $payload): AppointmentExternalEvent
```

Implementaciones futuras:

```text
GoogleCalendarAppointmentProvider
CalendlyAppointmentProvider
DoctoraliaAppointmentProvider
```

Servicio orquestador:

```text
App\Services\AppointmentSyncService
```

## Manejo De Conflictos

Conflictos posibles:

1. La cita cambia en OdonCRM y en proveedor externo al mismo tiempo.
2. El proveedor cancela una cita ya confirmada internamente.
3. El proveedor modifica horario y OdonCRM tiene actividad asociada.
4. Llega webhook sin paciente identificable.

Politica inicial recomendada:

```text
El cambio externo actualiza la tabla local, pero si existe conflicto sensible se guarda sync_error y requiere revision humana.
```

## Webhooks

Ruta sugerida:

```text
POST /webhook/appointments/{provider}
```

Controlador:

```text
AppointmentExternalWebhookController
```

Validaciones:

1. Firma o token del proveedor.
2. Idempotencia por ID externo y timestamp.
3. Logging sin exponer datos sensibles.
4. Respuesta rapida y procesamiento en cola si el payload es pesado.

## Idempotencia

Cada evento externo debe poder procesarse varias veces sin duplicar citas.

Claves recomendadas:

```text
external_provider
external_appointment_id
external_event_id
```

Si el proveedor no envia `external_event_id`, usar hash del payload relevante.

## Tests Sugeridos

1. Crear cita local y simular respuesta externa.
2. Recibir webhook externo y actualizar cita existente.
3. Recibir webhook externo y crear cita nueva.
4. No duplicar cita con mismo ID externo.
5. Guardar `sync_error` cuando el proveedor falle.
