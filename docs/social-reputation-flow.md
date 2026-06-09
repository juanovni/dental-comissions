# Fase 2 - Flujo funcional de Reputacion Digital

## Objetivo

Definir el flujo operativo y funcional del modulo de Reputacion Digital para Instagram y
Facebook. Esta fase establece como entran los comentarios, como se clasifican, que estados
pueden tener, que acciones puede tomar el administrador y que reglas protegen la reputacion
de la clinica sin salir del MVP.

## Decision base

El MVP usara dos mecanismos de entrada, con prioridad en sincronizacion programada:

1. Sincronizacion programada como flujo principal.
2. Webhooks de Meta como complemento para acelerar deteccion cuando esten disponibles.

La sincronizacion programada sera la fuente operativa principal porque es mas controlable,
facilita reintentos y reduce dependencia de que Meta entregue todos los eventos en tiempo
real.

## Alcance funcional del MVP

- Plataformas iniciales: Facebook Pages e Instagram Business/Creator conectado a una Page.
- Sincronizacion cada 5 minutos.
- Importacion inicial de comentarios de los ultimos 30 dias.
- Revision recurrente de publicaciones de los ultimos 30 dias.
- Clasificacion de todos los comentarios con IA.
- Bandeja de revision administrativa.
- Respuestas sugeridas por IA, siempre editables antes de enviar.
- Responder comentarios manualmente desde el sistema.
- Ocultar comentarios manualmente con confirmacion, solo si la API/permisos lo permiten.
- No eliminar comentarios en el MVP.
- No responder automaticamente.
- Historial completo de acciones.

## Flujo principal por sincronizacion

1. Scheduler ejecuta sincronizacion cada 5 minutos.
2. El sistema obtiene cuentas sociales activas.
3. Para cada cuenta, obtiene publicaciones de los ultimos 30 dias.
4. Para cada publicacion, consulta comentarios recientes.
5. Cada comentario se compara por `platform` + `external_comment_id`.
6. Si no existe, se guarda como comentario nuevo.
7. La IA clasifica el comentario y devuelve JSON estructurado.
8. El sistema asigna estado, prioridad, riesgo reputacional y accion sugerida.
9. El comentario aparece en la bandeja de revision.
10. El administrador revisa y decide responder, ocultar, ignorar, marcar como spam o escalar.
11. Toda accion queda registrada en historial.

## Flujo complementario por webhook

1. Meta envia evento al webhook cuando detecta un cambio de comentario.
2. Laravel recibe el payload y lo registra para auditoria.
3. El sistema intenta resolver cuenta, publicacion y comentario.
4. Si el comentario no existe, lo guarda y lo envia a clasificacion IA.
5. Si el comentario ya existe, actualiza metadatos relevantes.
6. La sincronizacion programada sigue activa como respaldo.

## Estados del comentario

| Estado | Significado | Uso |
| --- | --- | --- |
| `new` | Comentario guardado, pendiente de clasificacion | Estado temporal |
| `classified` | IA clasifico el comentario | Listo para revision |
| `review_required` | Requiere revision humana obligatoria | Quejas, temas sensibles, riesgo alto |
| `responded` | El admin envio respuesta desde el sistema | Cierre normal |
| `hidden` | El admin oculto el comentario manualmente | Spam/ofensivo confirmado |
| `ignored` | El admin decidio no actuar | Comentario sin accion necesaria |
| `marked_as_spam` | El admin lo marco como spam | Control interno y metricas |
| `escalated` | Requiere atencion de admin principal o equipo responsable | Casos delicados |
| `error` | Fallo API, IA o procesamiento | Requiere revision tecnica |

## Clasificaciones IA

| Clasificacion | Descripcion | Accion sugerida comun |
| --- | --- | --- |
| `normal` | Comentario neutro sin accion relevante | Ignorar o revisar |
| `sales_lead` | Interes claro en cita, precio, ubicacion o informacion | Responder y derivar a WhatsApp |
| `commercial_question` | Pregunta comercial general | Responder |
| `complaint` | Queja real sobre atencion, cobro, espera o experiencia | Revision humana y respuesta cuidadosa |
| `negative_opinion` | Opinion negativa sin queja concreta | Revisar, responder si conviene |
| `spam` | Promocion externa, enlaces sospechosos o contenido repetitivo | Marcar spam u ocultar manualmente |
| `offensive` | Insultos, ataques o lenguaje agresivo | Revision y posible ocultamiento manual |
| `positive` | Comentario positivo o testimonio | Agradecer |
| `medical_sensitive` | Pregunta o afirmacion medica sensible | Revision humana obligatoria |
| `legal_sensitive` | Amenaza legal, denuncia o acusacion grave | Escalar |
| `needs_human_review` | Ambiguo o potencialmente delicado | Revision humana |

## Sentimiento

- `positive`: favorable para la clinica.
- `neutral`: informativo o sin carga emocional clara.
- `negative`: desfavorable o molesto.
- `mixed`: mezcla de elogio y reclamo.

## Prioridades

| Prioridad | Criterios |
| --- | --- |
| `critical` | Amenaza, tema legal, acusacion grave, crisis reputacional o seguridad del paciente |
| `high` | Queja real, mala atencion, comentario ofensivo, tema medico sensible |
| `medium` | Lead comercial, pregunta de precio, cita, horarios o ubicacion |
| `low` | Comentario positivo, neutro o normal |

## Riesgo reputacional

El campo `reputation_risk` mide posible impacto publico sobre la clinica, separado de la
prioridad operativa.

- `low`: sin riesgo o comentario positivo.
- `medium`: duda publica o critica leve.
- `high`: queja visible, mala experiencia, acusacion de mala atencion.
- `critical`: amenaza, denuncia, acusacion grave, tema viralizable o legal.

Ejemplos:

- `Muy caro`: `negative_opinion`, prioridad `medium`, riesgo `medium`.
- `Me dejaron esperando 2 horas y nadie me respondio`: `complaint`, prioridad `high`, riesgo `high`.
- `Los voy a denunciar`: `legal_sensitive`, prioridad `critical`, riesgo `critical`.

## Acciones sugeridas

| Accion | Descripcion |
| --- | --- |
| `reply` | Responder publicamente o segun canal recomendado |
| `reply_and_route_to_whatsapp` | Responder y derivar a WhatsApp para conversion o atencion privada |
| `hide` | Sugerir ocultamiento manual con confirmacion |
| `review` | Requiere revision humana antes de actuar |
| `ignore` | No requiere accion |
| `mark_as_spam` | Marcar internamente como spam |
| `escalate` | Elevar a admin principal o responsable |
| `thank_user` | Agradecer comentario positivo |

## Canal recomendado de respuesta

El campo `response_channel` define donde conviene responder:

- `public`: respuesta visible en el comentario.
- `private`: mover la conversacion a mensaje privado o WhatsApp.
- `both`: responder publicamente con contencion y derivar a privado.
- `no_response`: no responder.

Reglas iniciales:

- Preguntas de ubicacion, horarios o precio simple: `public` o `both`.
- Leads comerciales: `public` con derivacion a WhatsApp.
- Quejas: `both`, con respuesta publica breve y seguimiento privado.
- Temas medicos sensibles: `private` o `both`, sin consejo medico automatico.
- Spam/ofensivo: `no_response` y posible ocultamiento manual.

## Contrato JSON de IA

La IA debe devolver un objeto con esta forma:

```json
{
  "classification": "sales_lead",
  "sentiment": "neutral",
  "priority": "medium",
  "reputation_risk": "low",
  "suggested_action": "reply_and_route_to_whatsapp",
  "response_channel": "public",
  "suggested_reply": "Hola, con gusto te ayudamos. Escribenos por WhatsApp para darte informacion personalizada y revisar disponibilidad.",
  "requires_human_review": false,
  "reason": "El usuario muestra interes comercial y puede convertirse en paciente."
}
```

Campos requeridos:

- `classification`
- `sentiment`
- `priority`
- `reputation_risk`
- `suggested_action`
- `response_channel`
- `suggested_reply`
- `requires_human_review`
- `reason`

## Reglas de revision humana obligatoria

Siempre requieren revision humana:

- Quejas reales sobre atencion, cobro, espera o mala experiencia.
- Comentarios ofensivos o ataques personales.
- Temas medicos sensibles.
- Amenazas o temas legales.
- Acusaciones graves.
- Comentarios con riesgo reputacional `high` o `critical`.
- Cualquier respuesta que pueda interpretarse como consejo medico.

## Reglas de ocultamiento

- No se ocultan quejas legitimas por defecto.
- Spam evidente puede sugerir ocultamiento, pero requiere confirmacion humana.
- Comentarios ofensivos pueden sugerir ocultamiento, pero requieren confirmacion humana.
- El sistema debe guardar quien oculto, cuando y la respuesta de la API.
- Si la API no permite ocultar, el comentario queda en `error` o `review_required` con mensaje claro.

## Reglas de respuesta

- Toda respuesta sugerida debe ser editable antes de enviarse.
- No se responderan automaticamente comentarios en el MVP.
- Las respuestas deben ser profesionales, amables y breves.
- Las preguntas comerciales deben derivar a WhatsApp cuando convenga.
- Las quejas deben responderse con contencion, sin discutir publicamente.
- Temas medicos sensibles deben invitar a consulta directa, no dar diagnosticos.

## Plantillas minimas de respuesta

El MVP debe contemplar plantillas para:

- Precio o informacion.
- Agendar cita.
- Ubicacion.
- Horarios.
- Queja o mala experiencia.
- Comentario positivo.
- Tema medico sensible.
- Spam/ofensivo.

## Metricas del MVP

El dashboard debe medir:

- Comentarios recibidos.
- Comentarios pendientes.
- Leads comerciales detectados.
- Preguntas comerciales sin responder.
- Quejas sin responder.
- Comentarios ofensivos/spam.
- Comentarios respondidos.
- Tiempo promedio de respuesta.
- Comentarios por red social.
- Distribucion por clasificacion, prioridad y riesgo reputacional.

## Historial requerido

Cada comentario debe conservar historial de:

- Comentario original.
- Payload/API origen cuando aplique.
- Clasificacion IA.
- Razon de la IA.
- Accion sugerida.
- Accion tomada.
- Usuario que tomo la accion.
- Fecha/hora de la accion.
- Respuesta enviada.
- Error de API si hubo.

## Casos operativos

### Lead comercial

Comentario: `Precio de limpieza?`

- Clasificacion: `sales_lead`
- Prioridad: `medium`
- Riesgo reputacional: `low`
- Accion sugerida: `reply_and_route_to_whatsapp`
- Canal: `public`

### Queja real

Comentario: `Me hicieron esperar 2 horas y nadie me atendio.`

- Clasificacion: `complaint`
- Prioridad: `high`
- Riesgo reputacional: `high`
- Accion sugerida: `escalate`
- Canal: `both`
- Revision humana: si

### Tema medico sensible

Comentario: `Estoy embarazada y me duele una muela, que puedo tomar?`

- Clasificacion: `medical_sensitive`
- Prioridad: `high`
- Riesgo reputacional: `medium`
- Accion sugerida: `review`
- Canal: `private`
- Revision humana: si

### Spam

Comentario: `Gana dinero rapido entrando a este link.`

- Clasificacion: `spam`
- Prioridad: `high`
- Riesgo reputacional: `low`
- Accion sugerida: `mark_as_spam` o `hide`
- Canal: `no_response`
- Revision humana: si para ocultar

### Comentario positivo

Comentario: `Excelente atencion, muy recomendados.`

- Clasificacion: `positive`
- Prioridad: `low`
- Riesgo reputacional: `low`
- Accion sugerida: `thank_user`
- Canal: `public`

## Fuera del MVP

- TikTok.
- YouTube.
- Google Reviews.
- Eliminacion de comentarios.
- Respuestas automaticas.
- Asignacion por usuario/equipo.
- CRM completo.
- Alertas por WhatsApp.
- Analisis de competencia.
- Campanas de marketing.

## Criterios de salida de fase 2

- Flujo principal y complementario definidos.
- Estados de comentarios definidos.
- Clasificaciones, prioridades y riesgo reputacional definidos.
- Acciones sugeridas y canales de respuesta definidos.
- Reglas de revision humana, ocultamiento y respuesta definidas.
- Contrato JSON de IA definido.
- Metricas e historial minimo definidos.
- Fuera de alcance del MVP documentado.

## Decision final de fase 2

El modulo debe funcionar como una herramienta de reputacion y conversion, no solo como una
bandeja de moderacion. El MVP prioriza detectar leads, quejas y riesgos reputacionales,
manteniendo todas las acciones sensibles bajo revision humana.
