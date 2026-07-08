# TODO Reputacion CRM

Este TODO pertenece solo al modulo de Reputacion Digital y Social CRM. No reemplaza ni mezcla tareas del sistema actual de comisiones.

## Fase 1: Identidad Social-Clinica

- [x] Crear tabla `social_identities` como etapa de lead social antes de crear pacientes clinicos.
- [x] Agregar campos CRM a `social_comments` para identidad, token, conversion y procedimiento sugerido.
- [x] Agregar campos de campana/ROI a `social_posts`.
- [x] Crear enums para estado de identidad social y estado de conversion social.
- [x] Crear modelo `SocialIdentity` con relaciones a `Patient` y `SocialComment`.
- [x] Agregar relaciones CRM a `SocialComment`, `SocialPost`, `Patient` y `Procedure`.
- [x] Agregar relacion CRM a `ActivityRecord` para atribucion de ROI.
- [x] Integrar creacion/busqueda de `social_identities` cuando se almacenan comentarios desde Meta.

## Fase 2: Handshake WhatsApp

- [x] Crear `SocialConversionService`.
- [x] Generar tokens `DNT-XXXXX` por comentario social.
- [x] Crear enlace de WhatsApp con mensaje prellenado y token.
- [x] Detectar tokens CRM en mensajes entrantes de WhatsApp.
- [x] Vincular automaticamente si el telefono existe en `patients.phone`.
- [x] Marcar lead como pendiente de crear ficha si el telefono no existe.
- [x] Registrar acciones de auditoria en `social_comment_actions`.
- [x] Integrar el handshake antes del rechazo de numeros no identificados en `WhatsappService`.

## Fase 3: Filament SMM Command Center

- [x] Agregar tabs: Leads, Quejas, Riesgo alto, Pendiente de ficha y Convertidos.
- [x] Agregar accion `Derivar a WhatsApp`.
- [x] Agregar accion `Vincular paciente existente`.
- [x] Agregar accion `Crear ficha de paciente desde lead` con modal obligatorio.
- [x] Mostrar identidad social, paciente vinculado, token y procedimiento sugerido.
- [x] Convertir tabs a Inbox Pro con contadores y categorias finales: Leads, Crisis, Pacientes VIP y Atencion Medica.
- [x] Agregar alerta visual para riesgo critico en la tabla.
- [x] Ampliar vista 360 con contexto clinico, historial social y botones rapidos.

## Fase 4: ROI Social

- [x] Permitir asociar `activity_records` con origen social.
- [x] Calcular revenue por post usando `activity_records.internal_rate_snapshot`.
- [x] Crear widgets: revenue por post, conversion lead-paciente y fuga de seguimiento.

## Reglas Cerradas

- `social_identities` funciona como etapa de lead; no se crea `patients` hasta handshake o accion manual.
- Si el telefono del handshake ya existe en pacientes, se vincula automaticamente.
- Si el telefono no existe, el lead queda como pendiente de crear ficha.
- El ROI sale de `activity_records.internal_rate_snapshot`.
- Se mantiene Gemini como IA principal.
- Solo se permite ocultamiento automatico para spam u ofensivo.
- Quejas, temas medicos y legales se escalan, no se ocultan automaticamente.
- La creacion de paciente desde lead debe usar modal obligatorio para confirmar nombre completo.
