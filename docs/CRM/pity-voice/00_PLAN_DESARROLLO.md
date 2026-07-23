# Pity Voice - Plan de Desarrollo por Fases

## Objetivo

Construir una recepcionista virtual de voz para OdonCRM capaz de atender llamadas, identificar pacientes, consultar disponibilidad, agendar citas y transferir a una persona cuando la conversacion lo requiera.

La regla central del diseno es que Laravel/OdonCRM sea el cerebro de negocio. El proveedor de voz y el modelo realtime solo deben encargarse del audio, la conversacion y la invocacion de tools controladas por Laravel.

## Principios de Arquitectura

- No acoplar el MVP a WhatsApp Calling. Debe existir una capa `VoiceChannel` para poder usar WhatsApp Calling, Twilio, SIP, Vonage, Plivo o un canal web de prueba.
- No duplicar servicios de agenda. Reutilizar `AppointmentAvailabilityService`, `AppointmentSlotSearchService`, `AppointmentCreationService`, `AppointmentSlotOfferService` y `AppointmentWorkflowService`.
- No crear citas directamente desde una fecha enviada por la IA. Primero se debe crear un hold temporal del slot y luego confirmar con `hold_token`.
- No guardar llamadas como `SocialComment`. Voz debe tener sus propias tablas y eventos.
- No dejar que la IA decida reglas criticas. Laravel valida disponibilidad, pacientes, doctores, procedimientos, transferencias y confirmaciones.
- Todo evento de proveedor debe ser idempotente usando `provider` + `provider_event_id` cuando exista.

## Arquitectura Objetivo

```text
Paciente llama
    |
    v
Proveedor de voz
    |-- WhatsApp Calling
    |-- Twilio Voice
    |-- SIP/Vonage/Plivo
    |-- Web test
    |
    | eventos/webhooks de llamada
    v
VoiceChannelAdapter en Laravel
    v
VoiceSessionService
    |
    | crea/actualiza sesion realtime y audita eventos
    v
OpenAI Realtime / motor de voz
    |
    | tool calls HTTP firmados
    v
VoiceToolController
    v
VoiceToolService
    v
Servicios existentes de OdonCRM
    |-- AppointmentSlotSearchService
    |-- AppointmentCreationService
    |-- AppointmentWorkflowService
    |-- AppointmentSlotHold
    |-- WhatsappService
```

Nota importante: Laravel no debe estar en el loop de audio frame-by-frame salvo que el proveedor lo exija. Laravel debe crear sesiones, recibir eventos, guardar auditoria y ejecutar tools. El audio realtime debe fluir entre el proveedor de voz y el motor de voz con la menor latencia posible.

## Componentes Nuevos

### Modelos

- `VoiceCall`
- `VoiceEvent`

### Enums

- `VoiceCallStatus`
- `VoiceEventType`
- `VoiceChannelType`
- `VoiceHandoffReason`

### Servicios

- `VoiceSessionService`: crea y actualiza llamadas.
- `VoiceAgentService`: genera instrucciones y definicion de tools para el agente.
- `VoiceToolService`: ejecuta tools contra servicios existentes.
- `VoicePatientResolver`: identifica pacientes por telefono.
- `VoiceHandoffService`: decide y registra transferencias.
- `VoiceTranscriptService`: guarda transcripcion y resumen.
- `VoiceAppointmentHoldService`: adapta `AppointmentSlotHold` al flujo de voz con tokens, expiracion e idempotencia.

### Controladores

- `VoiceWebhookController`: recibe eventos del canal de voz.
- `VoiceToolController`: expone tools HTTP para el agente realtime.

### Interfaces

```php
interface VoiceChannel
{
    public function name(): VoiceChannelType;

    public function verifyWebhook(Request $request): bool;

    public function parseIncomingEvent(Request $request): VoiceChannelEvent;

    public function createRealtimeSession(VoiceCall $call): VoiceRealtimeSession;
}
```

No incluir `sendResponse()` en el contrato base del MVP. En una arquitectura realtime real, la respuesta de audio no siempre sale desde Laravel; puede salir desde OpenAI Realtime hacia Twilio Media Streams, SIP o WhatsApp Calling. Si un proveedor requiere respuesta HTTP sin realtime, ese comportamiento debe vivir en una interfaz separada, por ejemplo `SynchronousVoiceChannel`.

## Modelo de Datos

### `voice_calls`

- `id`
- `patient_id` nullable
- `appointment_id` nullable
- `channel`
- `provider`
- `provider_call_id` nullable
- `from_phone`
- `to_phone` nullable
- `status`
- `handoff_reason` nullable
- `started_at`
- `ended_at` nullable
- `duration_seconds` nullable
- `transcript` nullable
- `ai_summary` nullable
- `last_error` nullable
- `metadata` json nullable
- timestamps

### `voice_events`

- `id`
- `voice_call_id`
- `type`
- `direction` nullable
- `provider_event_id` nullable
- `payload` json
- timestamps

Indice recomendado:

```text
unique(voice_call_id, provider_event_id) parcial donde provider_event_id no sea null
```

En PostgreSQL debe implementarse como indice parcial para permitir multiples eventos internos sin `provider_event_id`.

## Tools del MVP

### `identify_patient`

Identifica un paciente por telefono E.164.

Input:

```json
{
  "phone_e164": "+593999999999"
}
```

Output:

```json
{
  "found": true,
  "patient_id": 12,
  "name": "Maria Perez"
}
```

### `get_available_slots`

Busca horarios disponibles usando servicios existentes de agenda.

Input:

```json
{
  "procedure_name": "Limpieza",
  "preferred_date": "2026-07-24",
  "preferred_period": "morning",
  "doctor_id": null,
  "timezone": "America/Guayaquil"
}
```

Output:

```json
{
  "slots": [
    {
      "datetime": "2026-07-24 10:00:00",
      "label": "viernes 24 de julio a las 10:00 AM",
      "doctor_id": 3,
      "doctor_name": "Dra. Ana Torres"
    }
  ]
}
```

### `hold_slot`

Retiene temporalmente un horario antes de crear la cita. Debe reutilizar `AppointmentSlotHold`, pero con una capa `VoiceAppointmentHoldService` para emitir un token opaco y validar expiracion.

Input:

```json
{
  "slot_datetime": "2026-07-24 10:00:00",
  "doctor_id": 3,
  "procedure_id": 1,
  "phone_e164": "+593999999999"
}
```

Output:

```json
{
  "hold_token": "abc123",
  "expires_at": "2026-07-17T22:30:00Z"
}
```

### `create_appointment`

Crea la cita usando un hold valido.

Reglas:

- Debe requerir `hold_token` valido y no expirado.
- Debe revalidar disponibilidad justo antes de crear la cita.
- Debe consumir o cerrar el hold para evitar doble uso.
- Debe ser idempotente si el proveedor reintenta el mismo tool call.

Input:

```json
{
  "hold_token": "abc123",
  "patient_name": "Maria Perez",
  "phone_e164": "+593999999999",
  "procedure_id": 1,
  "notes": "Paciente agenda por llamada con Pity Voice"
}
```

Output:

```json
{
  "appointment_id": 44,
  "status": "scheduled",
  "confirmation_message": "Tu cita fue agendada para el viernes 24 de julio a las 10:00 AM."
}
```

### `request_handoff`

Marca la llamada para transferencia humana.

Input:

```json
{
  "reason": "pain",
  "summary": "Paciente reporta dolor intenso y solicita hablar con recepcion."
}
```

Output:

```json
{
  "status": "handoff_required"
}
```

## Reglas del Agente

### Puede Hacer

- Saludar y explicar que es una recepcionista virtual.
- Identificar al paciente por telefono.
- Preguntar motivo de consulta.
- Consultar disponibilidad.
- Ofrecer hasta tres horarios.
- Crear cita solo despues de confirmar el horario con el paciente.
- Enviar confirmacion por WhatsApp despues de crear la cita.

### Debe Transferir

- Dolor intenso.
- Emergencia.
- Sangrado, trauma, infeccion o fiebre.
- Reclamos o paciente molesto.
- Consulta clinica compleja.
- Solicitud explicita de hablar con una persona.
- Fallos repetidos de tool o datos ambiguos.

### No Puede Hacer

- Diagnosticar.
- Prometer precios cerrados salvo que exista una regla parametrizada y aprobada.
- Confirmar una cita sin `appointment_id` devuelto por Laravel.
- Inventar disponibilidad si `get_available_slots` falla.
- Pedir datos clinicos sensibles que no sean necesarios para agendar.

## Fases de Desarrollo

## Fase 0 - Preparacion Tecnica

Objetivo: dejar el terreno listo sin tocar flujos existentes.

Entregables:

- Crear rama `feature/pity-voice-mvp`.
- Documentar arquitectura y decisiones.
- Confirmar servicios de agenda reutilizables.
- Definir contratos de tools.

Criterio de salida:

- Documento aprobado.
- No hay cambios en WhatsApp texto ni Social Inbox.

## Fase 1 - Base de Dominio Voice

Objetivo: persistir llamadas y eventos aunque todavia no exista proveedor real.

Entregables:

- Enums `VoiceCallStatus`, `VoiceEventType`, `VoiceChannelType`, `VoiceHandoffReason`.
- Migraciones `voice_calls` y `voice_events`.
- Modelos `VoiceCall` y `VoiceEvent`.
- Relaciones con `Patient` y `Appointment`.
- Indices idempotentes para eventos de proveedor.
- Factories y tests basicos.

Criterio de salida:

- Se puede crear una llamada y anexar eventos.
- Eventos duplicados con `provider_event_id` no rompen el sistema.

## Fase 2 - Tools Laravel Primero

Objetivo: construir el cerebro operativo sin audio ni proveedor.

Entregables:

- `VoiceToolController`.
- `VoiceToolService`.
- Middleware de autenticacion para tools. No deben quedar publicas sin firma/token.
- `VoiceAppointmentHoldService` usando `AppointmentSlotHold`.
- Endpoints internos:
  - `POST /api/voice/tools/identify-patient`
  - `POST /api/voice/tools/get-available-slots`
  - `POST /api/voice/tools/hold-slot`
  - `POST /api/voice/tools/create-appointment`
  - `POST /api/voice/tools/request-handoff`
- Validacion de inputs normalizados.
- Idempotency key por tool call cuando el proveedor la envie.
- Tests feature de cada tool.

Criterio de salida:

- Desde Postman o tests se puede simular una llamada completa: identificar, buscar slot, retener, crear cita y confirmar.

## Fase 3 - Canal Web de Prueba

Objetivo: validar los tools y la conversacion sin comprar o depender de telefonia. Esta fase no debe pretender simular latencia/audio real; solo valida negocio y prompts.

Entregables:

- `WebTestVoiceChannel`.
- Pagina interna de prueba inicialmente por texto.
- Registro de transcript y tool calls.
- Flujo completo con datos reales de OdonCRM.

Criterio de salida:

- Un usuario interno puede simular la conversacion y crear una cita real sin llamada telefonica.

## Fase 4 - Integracion Realtime

Objetivo: conectar un motor de voz realtime manteniendo Laravel como tool backend.

Entregables:

- `VoiceAgentService` con instrucciones del agente.
- Definicion formal de tools para OpenAI Realtime o proveedor elegido.
- Guardado de transcript parcial/final.
- Manejo de errores y handoff por fallo tecnico.
- Timeouts claros para tools. Si Laravel no responde rapido, el agente debe disculparse y transferir o prometer seguimiento.

Criterio de salida:

- La IA conversa y ejecuta tools contra Laravel en ambiente controlado.

## Fase 5 - Primer Proveedor de Llamadas

Objetivo: recibir llamadas reales usando un proveedor concreto.

Opcion recomendada para MVP serio:

- Twilio Voice o proveedor SIP con streaming realtime.

Opcion experimental:

- WhatsApp Calling si la cuenta tiene soporte completo y estable.

Entregables:

- `TwilioVoiceChannel` o `WhatsAppCallingChannel`.
- `VoiceWebhookController`.
- Verificacion de webhook.
- Mapeo de eventos externos a `VoiceEvent`.
- Pruebas de llamadas cortadas, reintentos, audio incompleto y eventos duplicados.
- Pruebas de llamada real.

Criterio de salida:

- Paciente llama, Pity responde por voz, agenda cita y se guarda auditoria completa.

## Fase 6 - Confirmaciones y Auditoria

Objetivo: cerrar el ciclo operativo.

Entregables:

- Confirmacion por WhatsApp al crear cita.
- Resumen de llamada en `ai_summary`.
- Timeline de eventos de llamada.
- Indicadores basicos:
  - llamadas atendidas
  - citas creadas
  - handoffs
  - duracion promedio

Criterio de salida:

- Admin puede revisar que paso en una llamada sin escuchar todo el audio.

## Fase 7 - Handoff Humano

Objetivo: manejar casos que no debe resolver la IA.

Entregables:

- Estados `handoff_required` y `handoff_completed`.
- Motivo estructurado de handoff.
- Notificacion interna.
- Transferencia real si el proveedor lo permite.

Criterio de salida:

- Emergencias, reclamos y solicitud humana no terminan en respuestas automatizadas inseguras.

## Riesgos y Decisiones

### WhatsApp Calling

Riesgo: disponibilidad, permisos y capacidades pueden variar segun cuenta/app.

Decision: no bloquear el MVP por WhatsApp Calling. Implementar `VoiceChannel`.

### Doble Reserva

Riesgo: la IA ofrece un slot y otro canal lo toma antes de confirmar.

Decision: usar `hold_slot` antes de `create_appointment`.

### Seguridad de Tools

Riesgo: un endpoint de tool expuesto podria crear citas o leer disponibilidad sin autorizacion.

Decision: tools con middleware dedicado, token/firma por proveedor, rate limit e idempotency key.

### Latencia de Agenda

Riesgo: la conversacion de voz se siente rota si buscar horarios tarda demasiado.

Decision: limitar busquedas, cachear opciones durante la llamada y devolver respuestas pequenas.

### Seguridad Clinica

Riesgo: el paciente hace preguntas medicas o describe emergencia.

Decision: transferir. No diagnosticar.

### Costos y Latencia

Riesgo: llamadas realtime pueden ser costosas y sensibles a latencia.

Decision: probar primero con Web/Test y luego con proveedor real.

## No Hacer en MVP

- No presupuestos por voz.
- No diagnosticos.
- No multi-sede.
- No dashboards avanzados.
- No transferencias telefonicas complejas antes de validar provider.
- No mezclar llamadas con `SocialComment`.
- No exponer tools sin autenticacion.
- No construir integracion profunda con varios proveedores al mismo tiempo.

## Primer Sprint Recomendado

1. Crear enums, migraciones y modelos `VoiceCall` / `VoiceEvent`.
2. Crear `VoiceToolController` y `VoiceToolService`.
3. Implementar `identify_patient` y `get_available_slots`.
4. Implementar middleware de tools e idempotencia minima.
5. Implementar `VoiceAppointmentHoldService` sobre `AppointmentSlotHold`.
6. Implementar `hold_slot` y `create_appointment` usando servicios existentes.
7. Agregar tests feature para flujo completo sin audio.
