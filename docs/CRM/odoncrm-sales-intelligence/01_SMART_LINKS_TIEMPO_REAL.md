# Fase 1: Inteligencia de Smart Links y Tiempo Real

## Objetivo

Transformar la landing publica del Smart Link en un sensor activo de intencion que capture metadatos, eventos de video, clics y actividad en tiempo real.

## Estado Actual Del Sistema

Ya existen rutas `/v/{trackingToken}` y `/v/{trackingToken}/event`, controlador `SocialSmartLinkController`, vista `resources/views/social/smart-link.blade.php`, tabla `social_link_events`, scoring y alertas internas.

## Alcance De La Fase

1. Capturar `utm_source`, `utm_medium`, `utm_campaign` y `treatment_id` desde URL.
2. Registrar esos metadatos en eventos de tracking.
3. Sobrescribir contexto del clic cuando la URL trae parametros explicitos.
4. Agregar tracking de video con hitos de reproduccion.
5. Disparar evento broadcast `LeadActivityDetected` por cada evento recibido.
6. Ajustar encabezado dinamico de la landing.
7. Agregar boton WhatsApp sticky con token.

## Cambios Backend

### Controlador

Archivo principal: `app/Http/Controllers/SocialSmartLinkController.php`.

Cambios esperados:

1. En `show()`, leer parametros de URL: `utm_source`, `utm_medium`, `utm_campaign`, `treatment_id`.
2. Validar `treatment_id` contra `procedures.id` antes de usarlo.
3. Pasar metadatos normalizados a la vista.
4. En `track()`, aceptar nuevos `event_type`: `video_start`, `video_25`, `video_50`, `video_75`, `video_complete`, `whatsapp_click`.
5. Guardar metadatos del clic en `social_link_events.metadata`.
6. Disparar `LeadActivityDetected` despues de crear cada evento.

### Evento Broadcast

Crear evento: `app/Events/LeadActivityDetected.php`.

Payload recomendado:

```php
[
    'lead_id' => $comment->id,
    'tracking_token' => $comment->tracking_token,
    'event_type' => $event->event_type,
    'interest_score' => $comment->interest_score,
    'hot_lead' => filled($comment->hot_lead_at),
    'created_at' => $event->created_at?->toISOString(),
]
```

Canal recomendado: `private-admin-notifications`.

## Cambios Frontend/UI

Archivo principal: `resources/views/social/smart-link.blade.php`.

Cambios esperados:

1. Encabezado dinamico: `Hola {{ $leadName }}, tu plan dental esta listo`.
2. Agregar boton WhatsApp sticky con `position: fixed; bottom: 20px; right: 20px;`.
3. Enviar evento `whatsapp_click` antes de abrir WhatsApp.
4. Agregar listener sobre `<video>` para eventos `play`, `timeupdate` y `ended`.
5. Enviar hitos una sola vez por sesion: 25%, 50%, 75%, complete.
6. Mantener compatibilidad si el medio es `iframe`, registrando al menos `video_start` por clic/interaccion cuando no se pueda medir progreso real.

## Base De Datos

No es obligatorio crear tabla nueva. Usar `social_link_events.metadata` para los metadatos del clic.

Si se requiere analitica mas directa, evaluar en una fase posterior columnas agregadas o tabla especializada.

## Archivos A Modificar

1. `app/Http/Controllers/SocialSmartLinkController.php`
2. `app/Events/LeadActivityDetected.php`
3. `routes/channels.php`
4. `resources/views/social/smart-link.blade.php`
5. `app/Services/SocialLeadScoringService.php`
6. `app/Services/SocialCrmSettingsService.php`

## Criterios De Aceptacion

1. Al abrir `/v/{token}?utm_source=IG&utm_medium=dm&utm_campaign=junio&treatment_id=1`, el evento `view` guarda esos metadatos.
2. El video envia `video_start`, `video_25`, `video_50`, `video_75` y `video_complete` cuando corresponde.
3. Cada evento recibido dispara `LeadActivityDetected`.
4. El boton sticky de WhatsApp aparece en desktop y mobile.
5. El CTA de WhatsApp incluye el token `DNT-XXXXX`.
6. La landing sigue funcionando aunque no exista video configurado.

## Riesgos / Dependencias

1. Reverb debe estar configurado antes de validar tiempo real completo.
2. Los iframes externos no permiten medir progreso real sin API especifica del proveedor.
3. Hay que evitar duplicar scoring excesivo por eventos repetidos.

## Checklist De Implementacion

1. Actualizar validacion de eventos en `track()`.
2. Normalizar metadatos de URL.
3. Crear evento broadcast.
4. Configurar canal privado.
5. Agregar tracking JS de video.
6. Agregar CTA sticky.
7. Probar apertura, video y WhatsApp.
8. Ejecutar tests.
