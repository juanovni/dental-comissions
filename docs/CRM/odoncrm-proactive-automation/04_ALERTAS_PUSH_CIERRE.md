# Fase 4 - Alertas Push De Cierre

## Objetivo

Disparar notificaciones al personal cuando la IA detecte una oportunidad de cierre inminente en WhatsApp o cuando el comportamiento del Smart Link indique alta intencion.

## Estado Actual

El sistema ya tiene:

1. `SocialLeadAlert` para alertas persistentes.
2. `SocialLeadAlertService` para crear y resolver alertas.
3. `LeadActivityDetected` para broadcast en tiempo real.
4. `SocialLeadNotificationCenter` como campana en Filament.
5. Canal privado `admin-notifications`.

## Cambio Propuesto

Agregar una alerta especifica:

```text
closing_opportunity
```

Y una notificacion Laravel:

```text
App\Notifications\ClosingOpportunityDetectedNotification
```

## Disparadores

### Desde WhatsApp

Condicion:

```text
intent in [appointment_interest, ready_to_book]
closing_opportunity_score >= 75
```

Accion:

```text
Crear SocialLeadAlert
Enviar Notification a usuarios responsables
Broadcast evento ClosingOpportunityDetected
```

### Desde Smart Link

Condicion:

```text
recent_engagement_score >= 90
last_engagement_event_type in [whatsapp_click, video_complete, duration_threshold]
```

Accion:

```text
Crear SocialLeadAlert high_intent_activity
Actualizar campana en tiempo real
```

## Destinatarios

Primera version:

```text
Usuarios administradores activos
```

Version posterior:

```text
Usuario asignado al lead
Equipo comercial
Coordinador clinico
Doctor asignado por procedimiento
```

## Payload De Alerta

```json
{
  "social_comment_id": 123,
  "tracking_token": "DNT-ABCDE",
  "lead_name": "Maria Perez",
  "procedure": "Implantes dentales",
  "intent": "ready_to_book",
  "closing_opportunity_score": 86,
  "recent_engagement_score": 110,
  "message_excerpt": "Me gustaria agendar para manana",
  "recommended_action": "Responder en WhatsApp y confirmar horario"
}
```

## Canales

### In-App

Usar `SocialLeadAlert` y `SocialLeadNotificationCenter`.

### Broadcast

Crear evento:

```text
App\Events\ClosingOpportunityDetected
```

Canal:

```text
private-admin-notifications
```

### Database Notification

Usar tabla `notifications` de Laravel si se requiere historial por usuario.

### Push Web

Fase posterior. Puede integrarse con Web Push API, Firebase Cloud Messaging o proveedor externo.

## Mensajes Sugeridos

Titulo:

```text
Oportunidad de cierre
```

Mensaje:

```text
El lead Maria Perez quiere agendar una valoracion de Implantes dentales. Requiere seguimiento inmediato.
```

## Seguridad

1. No notificar como cierre si hay bandera clinica sensible sin revision.
2. Priorizar humano si la IA detecta urgencia medica.
3. Evitar duplicados abiertos por lead y tipo de alerta.
4. Registrar resolucion de alerta cuando el equipo marca contactado o crea cita.

## Tests Sugeridos

1. IA con `ready_to_book` crea alerta `closing_opportunity`.
2. Alerta no se duplica si ya existe abierta.
3. Notificacion se envia a usuarios esperados.
4. Evento broadcast contiene payload minimo.
5. Resolver alerta actualiza `resolved_at` y `resolved_by`.
