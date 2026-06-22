# Rollout Seguro - Auto Respuestas Comerciales Meta

## Objetivo

Activar auto-respuestas comerciales en Facebook/Instagram sin riesgo reputacional, empezando en `dry-run`, luego en una cuenta piloto y finalmente en producción.

## Estado Inicial Recomendado

Estos valores deben ser el punto de partida:

```text
social_auto_reply_enabled = false
social_auto_reply_dry_run = true
social_auto_reply_use_ai = true
social_auto_reply_use_smart_link = true
social_auto_reply_allowed_social_account_ids = []
```

## Control De Rollout Por Cuenta

Existe la configuración:

```text
social_auto_reply_allowed_social_account_ids
```

Comportamiento:

- `[]`: aplica a todas las cuentas sociales activas.
- `[1, 3]`: solo aplica a los IDs internos `social_accounts.id` 1 y 3.

Recomendación:

- Durante publicación real, no dejar `[]` hasta terminar la prueba piloto.
- Activar primero una sola cuenta piloto.
- Confirmar que el ID corresponde a la cuenta Meta correcta desde Configuración / Cuentas sociales.

## Requisitos Antes De Activar

- Queue worker activo.
- `BROADCAST_CONNECTION` estable, preferiblemente Reverb funcionando.
- Meta token vigente.
- Permisos Meta revisados:
  - `pages_read_engagement`
  - `pages_manage_engagement`
  - `instagram_basic`
  - `instagram_manage_comments`
  - `pages_show_list`
- Smart Link público accesible desde internet.
- WhatsApp business phone configurado.
- `APP_URL` correcto para generar links públicos.

## Paso 1 - Dry-Run Global

Configurar:

```text
social_auto_reply_enabled = true
social_auto_reply_dry_run = true
social_auto_reply_allowed_social_account_ids = []
```

Validar durante 24 a 48 horas:

- Mensajes generados en `SocialInbox`.
- Mensajes con cabecera correcta: `👋 Te saluda {empresa}`.
- Smart Link correcto.
- No aparecen precios.
- No aparecen diagnósticos.
- No aparecen promesas de resultado.
- Comentarios sensibles se omiten.
- Comentarios de queja/reclamo se omiten.
- No se publican respuestas en Meta.

## Paso 2 - Dry-Run Con Cuenta Piloto

Configurar:

```text
social_auto_reply_enabled = true
social_auto_reply_dry_run = true
social_auto_reply_allowed_social_account_ids = [ID_CUENTA_PILOTO]
```

Validar:

- Solo la cuenta piloto genera mensajes.
- Otras cuentas no generan auto-respuestas.
- El flujo aparece correctamente en el historial del lead.

## Paso 3 - Publicación En Cuenta Piloto

Configurar:

```text
social_auto_reply_enabled = true
social_auto_reply_dry_run = false
social_auto_reply_allowed_social_account_ids = [ID_CUENTA_PILOTO]
```

Validar con comentarios controlados:

- Facebook publica en `/{comment-id}/comments`.
- Instagram publica en `/{comment-id}/replies`.
- Se guarda `auto_replied_at`.
- Se guarda `auto_reply_external_id`.
- Se registra `AutoReplySent`.
- No se responde dos veces el mismo comentario.

## Paso 4 - Monitoreo De Piloto

Monitorear mínimo 24 horas:

- `AutoReplySent`.
- `AutoReplyFailed`.
- `AutoReplySkipped`.
- Clicks al Smart Link.
- Clicks a WhatsApp.
- Leads que pasan a cita.
- Comentarios públicos reales para revisar tono.

Si hay errores de Meta:

- Revisar token.
- Revisar permisos.
- Revisar endpoint por plataforma.
- Volver a `social_auto_reply_dry_run = true` si hay dudas.

## Paso 5 - Expansión Gradual

Agregar cuentas al allowlist una por una:

```text
social_auto_reply_allowed_social_account_ids = [ID_CUENTA_1, ID_CUENTA_2]
```

Validar cada cuenta antes de sumar otra.

## Apagado Rápido

Para detener todo:

```text
social_auto_reply_enabled = false
```

Para seguir generando sin publicar:

```text
social_auto_reply_dry_run = true
```

Para limitar a una cuenta específica:

```text
social_auto_reply_allowed_social_account_ids = [ID_CUENTA_SEGURA]
```

## Criterios Para Pasar A Producción Completa

- 0 respuestas con precios.
- 0 respuestas con diagnóstico.
- 0 respuestas con promesas clínicas.
- 0 duplicados.
- Tasa de errores Meta aceptable.
- Smart Links funcionando.
- Equipo administrativo entiende cómo apagar/reintentar.
- Revisión manual de una muestra de comentarios publicados.

## Riesgos Operativos

- Token Meta expirado.
- Permisos insuficientes.
- Comentarios sensibles mal clasificados.
- Links públicos mal configurados.
- Respuestas repetitivas percibidas como bot.
- Activar sin allowlist y publicar en todas las cuentas.

## Recomendación Final

No activar publicación real con `social_auto_reply_allowed_social_account_ids = []` hasta tener al menos una cuenta piloto validada.
