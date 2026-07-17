# Fase 1 - Modelo De Llamadas, Auditoria Y Seguridad

## Objetivo

Crear la base en Laravel para registrar llamadas, transcripciones, decisiones del agente, errores, transferencias y acciones ejecutadas.

Sin auditoria no se debe conectar Retell a pacientes reales.

## Modelos Recomendados

Entidad principal:

```text
VoiceCall
```

Tabla sugerida:

```text
voice_calls
```

Campos:

```text
id
provider
provider_call_id
direction
from_phone
to_phone
patient_id
appointment_id
status
intent
outcome
urgent_detected
complaint_detected
human_handoff_required
human_handoff_reason
started_at
ended_at
duration_seconds
transcript
summary
metadata
created_at
updated_at
```

Tabla de acciones:

```text
voice_call_actions
```

Campos:

```text
id
voice_call_id
action_name
request_payload
response_payload
success
error_code
error_message
created_at
```

## Enums Sugeridos

```text
VoiceCallStatus:
started
in_progress
resolved
transferred
failed
abandoned
```

```text
VoiceCallIntent:
general_info
book_appointment
reschedule_appointment
cancel_appointment
complaint
urgent
unknown
```

```text
VoiceCallOutcome:
appointment_created
appointment_rescheduled
appointment_cancelled
whatsapp_sent
transferred_to_human
no_availability
information_provided
failed_crm_error
no_action
```

## Seguridad De API

Las tools de Retell deben usar:

1. HTTPS.
2. Token de API exclusivo para Retell.
3. Firma HMAC si Retell permite headers custom.
4. Rate limit por IP/token.
5. Validacion estricta de payloads.
6. Logs sin exponer secretos.

## Idempotencia

Toda tool que cree o modifique datos debe aceptar:

```text
provider_call_id
idempotency_key
```

Ejemplo:

```text
retell:{call_id}:create_appointment:{slot_id}
```

Laravel debe devolver la misma respuesta si Retell repite la tool por latencia o retry.

## Retencion

Definir desde el inicio:

1. Cuantos dias guardar audio si existe.
2. Cuantos dias guardar transcript completo.
3. Si se conserva solo resumen despues de X dias.
4. Quien puede ver transcripciones en Filament.

## Entregables

1. Migraciones `voice_calls` y `voice_call_actions`.
2. Modelos con casts explicitos.
3. Enums en `App\Enums`.
4. Servicio `VoiceCallAuditService`.
5. Tests de creacion, actualizacion e idempotencia.

## Criterio De Listo

Se puede registrar una llamada fake completa con acciones, outcome, transcript y errores sin conectar Retell.
