# Plan de Desarrollo - Auto Respuestas Comerciales en Meta

## Objetivo

Implementar respuestas automáticas para comentarios comerciales recibidos desde Facebook e Instagram.

El sistema debe detectar comentarios clasificados como leads comerciales, generar una respuesta pública segura con IA, incluir un Smart Link trazable y registrar toda la actividad para auditoría, CRM y ROI.

## Principios

- La IA no diagnostica.
- La IA no informa precios.
- La IA no promete resultados.
- La respuesta debe ser breve, humana y comercial.
- El comentario público debe llevar al Smart Link.
- El Smart Link lleva a WhatsApp con tracking.
- Comentarios sensibles requieren revisión humana.
- No se debe responder dos veces el mismo comentario.

## Flujo General

```text
Meta Webhook
→ Guardar SocialComment
→ Clasificar comentario con IA
→ Si es lead comercial seguro
→ Generar respuesta automática
→ Publicar respuesta en Facebook/Instagram
→ Registrar auditoría
→ Usuario entra al Smart Link
→ Usuario continúa a WhatsApp
→ CRM mide conversión y ROI
```

---

## Fase 1 - Reglas y Configuración

### Objetivo

Crear la configuración necesaria para controlar cuándo y cómo se auto-responde.

### Cambios

- Agregar configuración CRM:
  - `auto_reply_enabled`
  - `auto_reply_dry_run`
  - `auto_reply_use_ai`
  - `auto_reply_company_name`
  - `auto_reply_header_template`
  - `auto_reply_template`
  - `auto_reply_max_attempts`
  - `auto_reply_allowed_classifications`
  - `auto_reply_use_smart_link`

### Valores Iniciales Recomendados

```text
auto_reply_enabled = false
auto_reply_dry_run = true
auto_reply_use_ai = true
auto_reply_company_name = Nombre de la clínica
auto_reply_header_template = 👋 Te saluda {empresa}
auto_reply_use_smart_link = true
```

### Criterios de Aceptación

- El admin puede activar/desactivar auto-respuestas.
- El sistema puede funcionar en modo dry-run.
- El nombre de empresa y plantilla son configurables.
- Por defecto no publica automáticamente hasta validar.

---

## Fase 2 - Estado y Auditoría

### Objetivo

Evitar duplicados y dejar trazabilidad completa.

### Cambios

Agregar campos a `social_comments`:

- `auto_replied_at`
- `auto_reply_external_id`
- `auto_reply_error`
- `auto_reply_attempts`
- `auto_reply_message`

Extender `SocialCommentActionType`:

- `AutoReplySent`
- `AutoReplyFailed`
- `AutoReplySkipped`
- `AutoReplyGenerated`

### Criterios de Aceptación

- Un comentario no puede auto-responderse dos veces.
- Cada intento queda registrado.
- Los errores de Meta quedan visibles para soporte/admin.
- En dry-run se guarda el mensaje generado sin publicarlo.

---

## Fase 3 - Meta Graph API Write-Back

### Objetivo

Permitir publicar respuestas en comentarios de Facebook e Instagram.

### Cambios

Extender `MetaSocialService` con:

```php
replyToComment(SocialComment $comment, string $message): array
```

Internamente:

```text
Facebook:
POST /{comment-id}/comments

Instagram:
POST /{comment-id}/replies
```

### Permisos Meta Necesarios

- `pages_read_engagement`
- `pages_manage_engagement`
- `instagram_basic`
- `instagram_manage_comments`
- `pages_show_list`

### Criterios de Aceptación

- Facebook publica respuesta pública correctamente.
- Instagram publica reply correctamente.
- El ID externo de la respuesta queda guardado.
- Errores de permisos/token quedan registrados claramente.

---

## Fase 4 - Generación Segura del Mensaje

### Objetivo

Generar una respuesta sutil, humana, breve y segura.

### Servicio Nuevo

Crear:

```php
SocialAutoReplyMessageService
```

Responsabilidades:

- Construir Smart Link.
- Preparar variables:
  - `{empresa}`
  - `{smart_link}`
  - `{whatsapp_link}`
  - `{tracking_token}`
  - `{procedure_name}`
  - `{lead_first_name}`
- Pedir respuesta a IA.
- Validar la respuesta.
- Aplicar fallback seguro si la IA falla.

### Prompt Base

```text
Eres asistente comercial de una clínica dental.

Redacta una respuesta pública breve para un comentario en redes sociales.

Reglas:
- Usa siempre esta cabecera: "👋 Te saluda {empresa}"
- No des diagnóstico.
- No menciones precios.
- No prometas resultados.
- No recomiendes tratamientos clínicos.
- No digas que eres IA.
- Usa tono humano, amable y sutil.
- Máximo 2 frases después de la cabecera.
- Incluye exactamente este link: {smart_link}
- Si el comentario es clínico, urgente, queja o sensible, responde solo: HUMAN_REVIEW_REQUIRED.

Comentario:
"{comment_text}"

Tratamiento sugerido:
"{procedure_name}"
```

### Fallback Seguro

```text
👋 Te saluda {empresa}

Hola, con gusto te ayudamos. Te dejamos la información inicial y el acceso para continuar por WhatsApp aquí: {smart_link}
```

### Criterios de Aceptación

- La respuesta siempre incluye Smart Link.
- La respuesta nunca incluye precios.
- La respuesta nunca diagnostica.
- La respuesta nunca promete resultados.
- Si la IA devuelve algo inseguro, se usa fallback.
- Si la IA pide revisión humana, no se publica.

---

## Fase 5 - Servicio de Auto-Respuesta

### Objetivo

Centralizar la lógica de decisión y ejecución.

### Servicio Nuevo

Crear:

```php
SocialAutoReplyService
```

Responsabilidades:

- Verificar si auto-respuesta está activa.
- Verificar si el comentario califica.
- Evitar duplicados.
- Validar que no requiera revisión humana.
- Validar riesgo reputacional.
- Generar tracking token si falta.
- Generar mensaje.
- Publicar o guardar dry-run.
- Registrar auditoría.

### Condiciones Para Responder

Responder solo si:

- `classification` es `SalesLead` o `CommercialQuestion`
- `requires_human_review = false`
- `reputation_risk` no es alto/crítico
- `status` no es spam/ignored
- No existe `auto_replied_at`
- No existe acción `AutoReplySent`
- El comentario tiene `external_comment_id`

### Condiciones Para No Responder

No responder si contiene:

- Dolor fuerte
- Sangrado
- Infección
- Urgencia
- Reclamo
- Queja
- Diagnóstico solicitado
- Caso clínico complejo
- Lenguaje agresivo
- Riesgo reputacional

### Criterios de Aceptación

- Leads comerciales simples se responden.
- Comentarios sensibles se omiten.
- Duplicados se evitan.
- Todos los skip/fail quedan auditados.

---

## Fase 6 - Job en Cola

### Objetivo

No bloquear webhooks ni sincronizaciones.

### Job Nuevo

Crear:

```php
SendSocialCommentAutoReply
```

### Flujo

```text
Job recibe social_comment_id
→ Recarga comentario
→ Ejecuta SocialAutoReplyService
→ Registra resultado
```

### Criterios de Aceptación

- El webhook responde rápido.
- Meta no bloquea el request principal.
- Fallos pueden reintentarse.
- El job respeta `auto_reply_max_attempts`.

---

## Fase 7 - Integración con Webhooks y Sync

### Objetivo

Disparar auto-respuesta después de clasificar comentarios.

### Cambios

Integrar después de:

```php
SocialCommentClassificationService::classify()
```

Cuando el comentario queda guardado/clasificado:

```text
if auto reply candidate
→ dispatch SendSocialCommentAutoReply
```

### Criterios de Aceptación

- Comentarios nuevos desde webhook pueden auto-responderse.
- Comentarios importados por sync pueden auto-responderse si aplica.
- Dry-run permite validar sin publicar.

---

## Fase 8 - Admin UI

### Objetivo

Dar control y visibilidad al administrador.

### Cambios En Filament

En `SocialCommentResource` o `SocialInbox`:

- Mostrar estado:
  - Pendiente
  - Generado
  - Auto-respondido
  - Error
  - Omitido
- Mostrar mensaje generado.
- Mostrar ID externo de respuesta.
- Mostrar error de Meta.
- Acción manual:
  - "Generar respuesta"
  - "Publicar respuesta"
  - "Reintentar auto-respuesta"

En configuración CRM:

- Activar/desactivar auto-respuesta.
- Activar/desactivar dry-run.
- Editar plantilla.
- Editar nombre empresa.
- Elegir Smart Link vs WhatsApp directo.

### Criterios de Aceptación

- Admin puede validar mensajes antes de activar producción.
- Admin puede reintentar fallos.
- Admin puede desactivar el sistema inmediatamente.

---

## Fase 9 - Tests

### Tests Recomendados

- Lead comercial despacha auto-reply job.
- Comentario no lead no despacha job.
- Comentario con revisión humana no se responde.
- Comentario con riesgo alto/crítico no se responde.
- Comentario ya respondido no se responde otra vez.
- Dry-run genera mensaje pero no llama a Meta.
- Facebook usa endpoint correcto.
- Instagram usa endpoint correcto.
- Error de Meta se guarda.
- Respuesta incluye Smart Link.
- Respuesta incluye cabecera `Te saluda {empresa}`.
- Respuesta no incluye precios.
- Respuesta no incluye diagnóstico.
- Fallback se usa si IA falla.

---

## Fase 10 - Rollout Seguro

### Paso 1

Activar `auto_reply_dry_run = true`.

Validar:

- Calidad de mensajes.
- Clasificaciones.
- Casos sensibles omitidos.
- Smart Links generados correctamente.

### Paso 2

Activar publicación solo en una cuenta Meta de prueba.

### Paso 3

Activar para Facebook.

### Paso 4

Activar para Instagram.

### Paso 5

Monitorear métricas:

- Comentarios respondidos.
- Clicks al Smart Link.
- Clicks a WhatsApp.
- Citas generadas.
- Errores de Meta.
- Comentarios omitidos por seguridad.

---

## Riesgos

- Meta puede rechazar permisos.
- Tokens pueden expirar.
- Instagram y Facebook tienen endpoints distintos.
- Auto-responder comentarios sensibles puede generar riesgo reputacional.
- Respuestas demasiado repetidas pueden parecer spam.
- Publicar links excesivamente puede activar controles de plataforma.

## Mitigaciones

- Dry-run inicial.
- Plantillas seguras.
- IA validada con fallback.
- Lista estricta de exclusión.
- Auditoría completa.
- Reintentos controlados.
- Configuración para apagar el sistema rápido.

## Recomendación Final

La primera versión debe salir así:

- `auto_reply_enabled = false`
- `auto_reply_dry_run = true`
- Smart Link como link principal
- WhatsApp dentro del Smart Link
- Respuesta pública breve
- IA controlada con fallback
- No responder temas clínicos/sensibles
- Registrar todo en auditoría

Cuando las respuestas generadas se validen en producción, activar publicación automática por cuenta.
