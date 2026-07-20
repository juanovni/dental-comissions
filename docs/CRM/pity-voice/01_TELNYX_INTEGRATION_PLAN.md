# Pity Voice - Plan de Integracion Telnyx

## Objetivo

Convertir el flujo probado en el simulador web en una llamada telefonica real usando Telnyx, manteniendo a Laravel/OdonCRM como cerebro de negocio para pacientes, disponibilidad, holds, citas, reprogramacion y handoff.

## Estado Actual

- Telnyx Voice API Application creada: `OdonCRM Pity Voice`.
- Numero Telnyx asignado: `+1 786 687 0733`.
- Webhook configurado en Telnyx:

```text
https://odon-crm.com/webhook/telnyx/voice/events
```

- Endpoint Laravel creado:

```text
POST /webhook/telnyx/voice/events
```

- Laravel recibe eventos reales de Telnyx:
  - `call.initiated`
  - `call.answered`
  - `call.gather.ended`
  - `call.hangup`
- Laravel contesta la llamada y reproduce saludo.
- Las llamadas se guardan en `voice_calls`.
- `gather_using_speak` actual esta capturando DTMF/digitos, no voz libre transcrita.

## Regla De Arquitectura

Telnyx solo debe manejar telefonia/audio. Laravel debe mantener la logica de negocio:

- identificar paciente
- interpretar fecha/periodo desde mensajes
- buscar disponibilidad real
- retener slots
- crear o reprogramar citas
- registrar eventos y transcript
- decidir handoff

## Variables De Entorno

```env
TELNYX_API_KEY=
TELNYX_API_URL=https://api.telnyx.com/v2
TELNYX_LANGUAGE=es-MX
TELNYX_VOICE=female
TELNYX_TRANSCRIPTION_LANGUAGE=es
TELNYX_TRANSCRIPTION_ENGINE=Telnyx
TELNYX_DEBUG=false
```

Notas:

- `TELNYX_API_KEY` no debe imprimirse ni compartirse en conversaciones.
- Despues de cambiar `.env`, ejecutar `php artisan config:clear` en el entorno que atiende `odon-crm.com`.
- La URL del webhook no es obligatoria en `.env` mientras se configure manualmente en Telnyx.

## Fase 1 - Estabilizar Webhook Y Auditoria

### Tareas

- [x] Crear `VoiceChannelType::Telnyx`.
- [x] Crear `TelnyxVoiceWebhookController`.
- [x] Registrar `POST /webhook/telnyx/voice/events`.
- [x] Excluir webhook de CSRF.
- [x] Crear `TelnyxVoiceService` para acciones API.
- [x] Contestar llamada en `call.initiated`.
- [x] Reproducir saludo en `call.answered`.
- [x] Finalizar llamada en `call.hangup`.
- [x] Evitar responder si `call.gather.ended` llega despues de `call.hangup`.
- [x] Mover logging completo de payload a flag `TELNYX_DEBUG=true`.
- [x] Guardar eventos Telnyx relevantes tambien en `voice_events`, no solo en `metadata`.
- [x] Agregar idempotencia por `data.id` del evento Telnyx.

### Criterios De Aceptacion

- Una llamada entrante crea un registro en `voice_calls` con `provider=telnyx`.
- El transcript registra al menos el saludo de Pity.
- `call.hangup` marca la llamada como `completed`.
- Eventos duplicados no duplican mensajes ni acciones.

## Fase 2 - Speech-To-Text Real

### Problema Actual

`gather_using_speak` devuelve payload con:

```json
{
  "digits": null,
  "status": "call_hangup"
}
```

Eso confirma que el flujo actual no transcribe voz libre. Sirve para DTMF, pero no para una recepcionista conversacional.

### Decision Tecnica

Prioridad 1: usar transcripcion nativa de Telnyx si entrega texto final por webhook.

Prioridad 2: si Telnyx no entrega transcripcion conversacional suficiente, usar Media Streaming + STT externo.

Opciones STT externas:

- OpenAI Realtime / Speech-to-Text
- Deepgram
- Google Speech-to-Text

### Tareas

- [x] Confirmar endpoint/accion Telnyx para iniciar transcripcion real.
- [x] Implementar `TelnyxVoiceService::startTranscription()`.
- [x] Implementar `TelnyxVoiceService::stopTranscription()` si aplica.
- [x] Manejar eventos de transcripcion Telnyx.
- [x] Normalizar payload de transcripcion a estructura interna:

```php
[
    'call_control_id' => '',
    'transcript' => '',
    'is_final' => true,
    'confidence' => null,
]
```

- [x] Ignorar transcripciones parciales si existen.
- [x] Deduplicar transcripciones finales.
- [x] Iniciar transcripcion despues de `call.speak.ended` para evitar capturar el saludo de Pity.
- [x] Detener transcripcion antes de responder con `speak`.

### Criterios De Aceptacion

- [ ] Al hablar en la llamada, Laravel recibe texto final.
- [x] El texto se guarda como `VoiceEventType::UserMessage`.
- [x] El transcript de `voice_calls` muestra usuario y asistente.
- [x] No se procesa dos veces la misma frase.

## Fase 3 - Turnos Conversacionales

### Flujo Objetivo

```text
call.initiated
    -> answer

call.answered
    -> speak saludo
    -> startTranscription

transcription.final
    -> VoiceAiService::sendMessage(call_id, transcript)
    -> speak respuesta Pity

call.speak.ended
    -> seguir escuchando o reactivar transcripcion

call.hangup
    -> completed
```

### Tareas

- [ ] Conectar transcripcion final con `VoiceAiService::sendMessage()`.
- [ ] Reproducir respuesta con `TelnyxVoiceService::speak()`.
- [ ] Evitar que Pity se escuche a si misma durante TTS.
- [ ] Si Telnyx transcribe mientras habla Pity, pausar transcripcion antes de `speak` y reanudar en `call.speak.ended`.
- [ ] Manejar `ended=true` de `VoiceAiService` con despedida y `hangup`.
- [ ] Manejar `handoff=true` con estado `handoff_required`.

### Criterios De Aceptacion

- Pity puede mantener al menos 3 turnos reales por llamada.
- El usuario puede pedir una cita y Pity ofrece slots reales.
- El usuario puede seleccionar una opcion ofrecida.
- Pity puede retener el slot y confirmar cita.

## Fase 4 - Integracion De Agenda En Llamada Real

### Escenarios Obligatorios

- [ ] Paciente nuevo agenda cita.
- [ ] Paciente existente agenda cita.
- [ ] Paciente existente reprograma cita futura activa.
- [ ] Solicitud para sabado/dia cerrado explica que no hay disponibilidad y ofrece alternativas.
- [ ] Frase `despues del almuerzo` ofrece horarios de tarde.
- [ ] Horario ocupado no se ofrece por conflicto en `appointments` o holds.
- [ ] Procedimiento generico usa procedimiento default si esta configurado.
- [ ] Urgencia o solicitud de humano marca `handoff_required`.

### Criterios De Aceptacion

- No se crean citas duplicadas para paciente con cita futura activa.
- Todo slot retenido proviene de `get_available_slots`.
- `create_appointment` solo usa `hold_token` emitido por backend.
- Google Calendar se actualiza si la cita reprogramada tenia `external_appointment_id`.

## Fase 5 - Observabilidad Admin

### Tareas

- [ ] Mostrar llamadas Telnyx en panel/admin.
- [ ] Mostrar transcript completo.
- [ ] Mostrar eventos Telnyx por llamada.
- [ ] Mostrar errores Telnyx/API.
- [ ] Mostrar cita enlazada si existe.
- [ ] Agregar filtros por estado, proveedor y fecha.

### Criterios De Aceptacion

- Un administrador puede abrir una llamada y entender que paso.
- Se puede diagnosticar si fallo por STT, TTS, agenda, IA o Telnyx.

## Fase 6 - Seguridad

### Tareas

- [ ] Configurar `TELNYX_PUBLIC_KEY` o secreto equivalente.
- [ ] Validar firma de webhook Telnyx en produccion.
- [ ] Rechazar webhooks invalidos.
- [ ] Mantener excepcion CSRF solo para ruta Telnyx.
- [ ] No loguear payload completo salvo `TELNYX_DEBUG=true`.
- [ ] Sanitizar logs para no exponer tokens, keys o datos sensibles.

### Criterios De Aceptacion

- Webhooks falsos no modifican llamadas.
- Logs de produccion no contienen secretos.

## Fase 7 - Pruebas

### Automatizadas

- [x] `call.initiated` crea `voice_calls`.
- [x] `call.hangup` completa llamada.
- [x] `call.answered` reproduce saludo.
- [x] Evento de transcripcion final crea mensaje de usuario.
- [x] Evento de transcripcion final llama `VoiceAiService`.
- [x] `call.speak.ended` reanuda escucha si aplica.
- [ ] Eventos duplicados son idempotentes.
- [ ] Webhook invalido se rechaza en produccion.

### Manuales

- [ ] Llamar al numero Telnyx desde Ecuador.
- [ ] Escuchar saludo de Pity.
- [ ] Decir: `Quiero una cita para limpieza despues del almuerzo`.
- [ ] Confirmar que ofrece horarios de tarde.
- [ ] Decir: `El primero`.
- [ ] Confirmar que retiene el slot correcto.
- [ ] Confirmar cita con nombre completo.
- [ ] Ver cita creada/reprogramada en admin.

## Riesgos

- Telnyx puede requerir plan pagado para debugging avanzado o funcionalidades realtime.
- La transcripcion nativa puede no ser suficiente para conversacion natural.
- Llamadas internacionales desde Ecuador pueden tener variabilidad de carrier.
- Si la transcripcion ocurre mientras Pity habla, puede capturar eco o respuestas propias.

## Orden Recomendado De Implementacion

1. Formalizar eventos y logging controlado.
2. Implementar transcripcion real.
3. Conectar transcripcion final con `VoiceAiService`.
4. Reproducir respuesta por voz y controlar turnos.
5. Validar flujo completo de agenda real.
6. Agregar seguridad de firma Telnyx.
7. Pulir panel admin y observabilidad.

## Ultima Actualizacion

2026-07-20
