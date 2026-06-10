# Plan de prueba - Reputacion Digital

## Objetivo

Validar el flujo local del modulo de Reputacion Digital sin depender de publicaciones reales
clasificacion procesa el comentario con IA o fallback local.

## Prerrequisitos

- Entorno local o testing.
- Migraciones ejecutadas.
- App corriendo en `http://localhost:8080`.
- Google Gemini configurado para probar IA real. Si falla, se usara fallback local.

```bash
docker compose exec dental.app php artisan migrate
```

## Crear comentario simulado

Endpoint:

```text
POST http://localhost:8080/test/meta/comment
```

Headers:

```text
Content-Type: application/json
Accept: application/json
```

Payload lead comercial:

```json
{
  "platform": "facebook",
  "account_id": "109923687366474",
  "account_name": "Junsc_22",
  "post_id": "test_post_1",
  "post_message": "Publicacion de prueba para reputacion digital",
  "comment_id": "test_comment_lead_1",
  "comment_text": "Quiero informacion sobre una limpieza dental",
  "author_name": "Cliente Prueba",
  "author_username": "cliente_prueba"
}
```

Respuesta esperada:

```json
{
  "status": "ok",
  "account": {},
  "post": {},
  "comment": {}
}
```

## Clasificar comentarios pendientes

```bash
docker compose exec dental.app php artisan social:classify-comments
```

Resultado esperado:

```text
Comentarios clasificados: 1.
```

## Verificar en Tinker

```bash
docker compose exec dental.app php artisan tinker
```

```php
$comment = App\Models\SocialComment::latest()->first();
$comment->status->value;
$comment->classification?->value;
$comment->priority?->value;
$comment->reputation_risk?->value;
$comment->suggested_action?->value;
$comment->response_channel?->value;
$comment->suggested_reply;
$comment->actions()->count();
```

Para el lead comercial se espera algo cercano a:

```text
status = classified
classification = sales_lead
priority = medium
reputation_risk = low
suggested_action = reply_and_route_to_whatsapp
response_channel = public
```

## Casos de prueba recomendados

### Queja real

```json
{
  "comment_id": "test_comment_complaint_1",
  "comment_text": "Me hicieron esperar 2 horas y nadie me atendio"
}
```

Esperado:

```text
status = review_required
classification = complaint
priority = high
reputation_risk = high
```

### Tema medico sensible

```json
{
  "comment_id": "test_comment_medical_1",
  "comment_text": "Estoy embarazada y me duele una muela, que puedo tomar?"
}
```

Esperado:

```text
status = review_required
classification = medical_sensitive
priority = high
```

### Spam

```json
{
  "comment_id": "test_comment_spam_1",
  "comment_text": "Gana dinero rapido entrando a este link http://spam.test"
}
```

Esperado:

```text
status = review_required
classification = spam
suggested_action = mark_as_spam
```

### Comentario positivo

```json
{
  "comment_id": "test_comment_positive_1",
  "comment_text": "Excelente atencion, muy recomendados"
}
```

Esperado:

```text
status = classified
classification = positive
suggested_action = thank_user
```
