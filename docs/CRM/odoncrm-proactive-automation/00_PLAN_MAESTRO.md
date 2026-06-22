# OdonCRM Proactive Automation - Plan Maestro

## Objetivo

Construir el flujo de automatizacion proactiva que conecta redes sociales, Smart Links, WhatsApp, agenda y ROI para que la clinica gestione leads mas calientes y trazables.

La automatizacion debe filtrar, calentar y priorizar oportunidades comerciales. No debe reemplazar al humano en decisiones clinicas, diagnosticos o aprobaciones sensibles.

## Contexto Actual

El sistema ya cuenta con una base funcional para:

1. Captura de comentarios sociales desde Meta.
2. Clasificacion con IA de intencion comercial, riesgo y respuesta sugerida.
3. Smart Links con token unico `DNT-XXXXX`.
4. Tracking de eventos como visita, permanencia, video y clic a WhatsApp.
5. `recent_engagement_score` e `interest_score`.
6. Pipeline Kanban comercial.
7. Alertas internas en `social_lead_alerts`.
8. Broadcast en tiempo real con Reverb.
9. Handshake WhatsApp por token.
10. Atribucion social a procedimientos mediante `ActivityRecord` y `SocialRoiService`.

## Nueva Vision

El siguiente bloque convierte el sistema en un flujo comercial completo:

1. El lead comenta o escribe por redes.
2. IA detecta intencion comercial.
3. El sistema genera Smart Link con token unico.
4. El lead consume contenido y genera eventos de engagement.
5. El score reciente mueve prioridad y etapa comercial.
6. El lead llega a WhatsApp con token.
7. El agente IA recupera contexto y saluda con intencion especifica.
8. Si detecta oportunidad de cierre, notifica al equipo.
9. Se crea una cita en tabla propia `appointments`.
10. La cita queda trazada hasta el contenido social que la origino.
11. Si luego existe agenda externa, `appointments` se sincroniza con el proveedor.

## Fases

1. Fase 1: Appointments y agenda interna.
2. Fase 2: WhatsApp Sales Agent.
3. Fase 3: Automatizacion Kanban por scoring.
4. Fase 4: Alertas push de cierre.
5. Fase 5: Trazabilidad ROI de citas.
6. Fase 6: Integraciones externas de agenda.
7. Fase 7: Roadmap de implementacion y validacion.

## Principios

1. Mantener la trazabilidad completa desde contenido social hasta cita y procedimiento.
2. Guardar datos internos aunque exista proveedor externo de agenda.
3. Usar campos parametrizables para umbrales, mensajes, estados y reglas.
4. No dar diagnosticos clinicos por IA.
5. No prometer tratamientos, precios definitivos ni resultados clinicos.
6. Escalar a humano cuando exista urgencia, dolor, riesgo medico o intencion clara de cierre.
7. Registrar cada automatizacion relevante en `social_comment_actions`.
8. Mantener UI y etiquetas en espanol.
9. Usar Enums para estados persistentes.
10. Agregar tests por servicio y flujo critico.

## Dependencias Existentes

1. `SocialComment` como lead social principal.
2. `SocialIdentity` para unir identidad social, telefono y paciente.
3. `SocialPost` como origen de contenido/campana.
4. `SocialLinkEvent` para engagement en Smart Link.
5. `SocialLeadAlert` para alertas operativas.
6. `WhatsappMessage` para mensajes entrantes/salientes.
7. `SocialConversionService` para token y handshake.
8. `WhatsappService` para webhook y envio WhatsApp.
9. `AiJsonService` para proveedor IA.
10. `SocialRoiService` para atribucion e ingresos.

## Validacion General

1. Ejecutar migraciones en PostgreSQL.
2. Probar Smart Link y tracking en `/v/{trackingToken}`.
3. Probar webhook WhatsApp con token valido, invalido e incompleto.
4. Probar creacion de cita desde lead social.
5. Probar alerta de oportunidad de cierre.
6. Probar sincronizacion externa en modo fake/mock antes de usar APIs reales.
7. Ejecutar `make test` o el comando equivalente disponible.
