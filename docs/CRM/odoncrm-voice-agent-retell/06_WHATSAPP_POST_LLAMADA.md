# Fase 5 - WhatsApp Post-Llamada Y Continuidad Omnicanal

## Objetivo

Usar WhatsApp Cloud API para confirmar y dar continuidad a lo gestionado por telefono sin duplicar mensajes ni aumentar costos innecesarios.

## Eventos Que Pueden Enviar WhatsApp

1. Cita creada.
2. Cita reprogramada.
3. Cita cancelada.
4. Solicitud registrada pero pendiente de recepcion.
5. Reclamo recibido y derivado.

## Eventos Que No Deben Enviar WhatsApp Automatico

1. Urgencia clinica grave si se transfirio a humano y no hay consentimiento de comunicacion.
2. CRM fallo y no se completo accion.
3. Paciente no confirmo identidad.
4. Conversacion abandonada sin resultado claro.

## Regla De Costos

No enviar WhatsApp si no aporta continuidad operativa. Un mensaje de confirmacion de cita si aporta. Un resumen de llamada generico normalmente no.

## Plantillas Sugeridas

Confirmacion:

```text
Hola {nombre}. Confirmamos tu cita para {procedimiento} el {fecha} a las {hora} en {sede}. Si necesitas cambiarla, responde este mensaje.
```

Solicitud pendiente:

```text
Hola {nombre}. Recibimos tu solicitud y nuestro equipo de recepcion te contactara para finalizar la coordinacion.
```

Reclamo:

```text
Hola {nombre}. Recibimos tu reclamo y fue derivado a recepcion para seguimiento.
```

## Integracion Con Tracking Social

Si el paciente viene de Smart Link o WhatsApp con token, la llamada debe preservar atribucion:

```text
voice_call.social_comment_id
voice_call.social_identity_id
appointment.social_comment_id
appointment.source = voice_ai
```

## Entregables

1. Integracion con `WhatsappService` existente.
2. Metodo de envio idempotente post-llamada.
3. Registro de mensaje saliente en `WhatsappMessage`.
4. Tests de no duplicacion.

## Criterio De Listo

Cada cita creada por voz puede enviar una confirmacion WhatsApp una sola vez y queda trazada al canal telefonico.
