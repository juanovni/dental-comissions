# Fase 1 - Validacion tecnica Meta para Reputacion Digital

## Objetivo

Validar la viabilidad tecnica del modulo de Reputacion Digital para Instagram y Facebook,
enfocado en moderacion semiautomatica de comentarios. El MVP debe leer comentarios,
clasificarlos con IA, sugerir acciones y permitir acciones manuales desde el panel. No se
eliminaran comentarios automaticamente en la primera version.

## Alcance inicial

- Plataformas: Facebook Pages e Instagram Business/Creator conectado a una pagina de Facebook.
- Modo de operacion: semiautomatico.
- Acciones permitidas en MVP: leer, clasificar, responder manualmente, ocultar manualmente si el permiso/API lo permite, ignorar y escalar.
- Acciones excluidas del MVP: eliminar comentarios, responder automaticamente sin revision humana y ocultar automaticamente comentarios sensibles.

## Prerrequisitos de cuenta

- App de Meta creada en Meta Developers.
- Business Manager configurado.
- Facebook Page administrada por el negocio.
- Cuenta de Instagram Business o Creator conectada a la Facebook Page.
- Usuario administrador con permisos sobre la pagina y la cuenta de Instagram.
- Token de acceso de larga duracion o flujo OAuth para renovar credenciales.
- Webhook publico HTTPS para recibir eventos de comentarios.

## Permisos esperados

Los permisos exactos deben confirmarse en Meta App Review segun el tipo de cuenta y el alcance
final, pero para el MVP se deben considerar:

- `pages_show_list`: listar paginas disponibles para el usuario autenticado.
- `pages_read_engagement`: leer contenido e interacciones de paginas.
- `pages_manage_engagement`: responder u ocultar comentarios de pagina si aplica.
- `instagram_basic`: leer informacion basica de cuenta de Instagram conectada.
- `instagram_manage_comments`: administrar comentarios de Instagram si aplica.
- `business_management`: acceder a activos del negocio cuando el flujo lo requiera.

## Capacidades a validar

| Capacidad | Facebook Page | Instagram Business | Decision MVP |
| --- | --- | --- | --- |
| Listar cuentas conectadas | Viable con permisos de paginas | Viable si IG esta conectada a Page | Requerido |
| Leer publicaciones | Viable | Viable | Requerido |
| Leer comentarios | Viable | Viable | Requerido |
| Recibir webhooks de comentarios | Viable con suscripcion | Viable con suscripcion adecuada | Requerido |
| Responder comentarios | Viable con permisos | Viable con permisos | Manual |
| Ocultar comentarios | Viable segun API/permisos | Requiere validacion por plataforma | Manual si disponible |
| Eliminar comentarios | Puede estar disponible en algunos casos | Requiere validacion | Fuera del MVP |

## Endpoints de referencia

Los endpoints finales deben validarse con el token real y la version vigente de Graph API.

### Facebook Pages

- `GET /me/accounts`: obtener paginas administradas por el usuario.
- `GET /{page-id}/posts`: obtener publicaciones de la pagina.
- `GET /{post-id}/comments`: obtener comentarios de una publicacion.
- `POST /{comment-id}/comments`: responder a un comentario.
- `POST /{comment-id}` con parametros soportados: ocultar o actualizar estado si la API/permisos lo permiten.

### Instagram Business

- `GET /{page-id}?fields=instagram_business_account`: obtener la cuenta de Instagram conectada.
- `GET /{ig-user-id}/media`: obtener publicaciones de Instagram.
- `GET /{ig-media-id}/comments`: obtener comentarios de una publicacion.
- `POST /{ig-comment-id}/replies`: responder a un comentario si el permiso lo permite.
- `POST /{ig-comment-id}` con parametros soportados: ocultar comentario si la API/permisos lo permiten.

### Webhooks

- Configurar webhook HTTPS en Meta Developers.
- Suscribir eventos de cambios para paginas e Instagram segun disponibilidad.
- Validar el token de verificacion igual que el webhook de WhatsApp existente.
- Registrar todos los payloads entrantes para auditoria y depuracion.

## Flujo tecnico propuesto

1. El administrador conecta Facebook/Instagram mediante OAuth o configura credenciales autorizadas.
2. El sistema guarda la cuenta social y sus identificadores externos.
3. Un comando de sincronizacion trae publicaciones y comentarios recientes.
4. El webhook recibe eventos nuevos de comentarios cuando Meta los emite.
5. Cada comentario se guarda evitando duplicados por plataforma e ID externo.
6. La IA clasifica el comentario y devuelve JSON estructurado.
7. El comentario aparece en una bandeja de revision con prioridad y accion sugerida.
8. El administrador decide responder, ocultar, ignorar, marcar como spam o escalar.
9. Toda accion queda registrada en historial.

## Contrato de clasificacion IA

La IA debe devolver JSON, no texto libre.

```json
{
  "classification": "commercial_question",
  "sentiment": "neutral",
  "priority": "medium",
  "suggested_action": "reply",
  "suggested_reply": "Hola, gracias por escribirnos. Para darte informacion personalizada, puedes contactarnos por WhatsApp.",
  "reason": "El usuario pregunta por informacion comercial."
}
```

Valores iniciales:

- `classification`: `normal`, `commercial_question`, `complaint`, `spam`, `offensive`, `positive`, `negative`, `needs_human_review`.
- `sentiment`: `positive`, `neutral`, `negative`, `mixed`.
- `priority`: `low`, `medium`, `high`, `critical`.
- `suggested_action`: `reply`, `hide`, `review`, `ignore`, `mark_as_spam`, `escalate`.

## Reglas de seguridad para MVP

- No eliminar comentarios desde el sistema.
- No ocultar automaticamente comentarios sin aprobacion humana.
- No responder automaticamente quejas, temas medicos, amenazas o casos sensibles.
- Guardar historial de cada accion manual.
- Guardar payloads/respuestas de API cuando haya errores.
- Permitir que el administrador edite cualquier respuesta sugerida antes de enviarla.
- Usar una lista de palabras de alto riesgo solo como senal de prioridad, no como decision final automatica.

## Riesgos y mitigaciones

| Riesgo | Impacto | Mitigacion |
| --- | --- | --- |
| App Review rechaza permisos | Bloquea produccion | Preparar screencast y caso de uso claro para Meta |
| Instagram no permite ocultar ciertos comentarios | Limita moderacion | Mantener accion como manual/condicional y mostrar error claro |
| Webhook no entrega todos los eventos | Perdida de comentarios | Agregar comando de sincronizacion programado como respaldo |
| IA clasifica mal un comentario | Accion incorrecta | MVP semiautomatico con revision humana obligatoria |
| Token expira | Se detiene sincronizacion | Implementar refresh/alertas de conexion en fase posterior |
| Comentarios negativos legitimos son tratados como spam | Riesgo reputacional | Separar `complaint` de `spam` y evitar borrado automatico |

## Criterios de salida de fase 1

- Permisos Meta documentados.
- Prerrequisitos de cuenta definidos.
- Endpoints iniciales identificados.
- Capacidades y restricciones del MVP claras.
- Riesgos principales documentados.
- Decision tecnica: iniciar con Instagram/Facebook en modo semiautomatico y sin eliminacion automatica.

## Decision final de fase 1

La fase 1 valida avanzar con Instagram y Facebook como primera integracion del modulo de
Reputacion Digital. El MVP debe priorizar lectura, clasificacion, bandeja de revision,
respuesta manual y ocultamiento manual si la API lo permite. TikTok, eliminacion de
comentarios y automatizacion fuerte quedan fuera del MVP.
