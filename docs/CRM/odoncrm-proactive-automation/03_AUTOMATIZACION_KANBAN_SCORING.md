# Fase 3 - Automatizacion Kanban y Scoring

## Objetivo

Mover y priorizar leads en el Kanban segun actividad reciente, score comercial y señales de WhatsApp.

El Kanban debe mostrar primero los leads con mayor probabilidad de cierre y moverlos cuando exista evidencia suficiente.

## Estado Actual

`SocialPipelineKanban` ya ordena tarjetas por:

```text
recent_engagement_score desc
last_engagement_at desc
interest_score desc
updated_at desc
```

`SocialLeadEngagementPriorityService` ya calcula `recent_engagement_score` con eventos recientes de Smart Link.

## Cambio Propuesto

Crear servicio:

```text
App\Services\SocialPipelineAutomationService
```

Responsabilidades:

1. Evaluar score reciente despues de cada evento.
2. Mover etapa cuando se cumplan reglas.
3. Registrar acciones en `social_comment_actions`.
4. Crear alertas cuando haya oportunidad operativa.
5. Evitar movimientos destructivos o regresivos sin humano.

## Reglas Iniciales

### Regla 1 - Nuevo A Calificado

Condicion:

```text
pipeline_stage = new
recent_engagement_score >= 50
```

Accion:

```text
Mover a qualified
Crear accion PipelineStageChanged
```

### Regla 2 - Calificado A Cita

Condicion:

```text
pipeline_stage in [new, qualified]
last_engagement_event_type = whatsapp_click
```

Accion:

```text
Mover a appointment
Crear alerta de seguimiento WhatsApp
```

### Regla 3 - Lead Caliente

Condicion:

```text
interest_score >= hotLeadThreshold
or recent_engagement_score >= salesUrgentScoreThreshold
```

Accion:

```text
Crear alerta hot_lead_created o high_recent_engagement
Mantener prioridad alta en Kanban
```

### Regla 4 - WhatsApp Ready To Book

Condicion:

```text
WhatsApp Sales Agent devuelve intent = ready_to_book
closing_opportunity_score >= 75
```

Accion:

```text
Mover a appointment
Crear alerta closing_opportunity
Notificar equipo
```

### Regla 5 - No Mover Perdidos O Ganados

Condicion:

```text
pipeline_stage in [won, lost]
```

Accion:

```text
No automatizar cambios
Solo registrar nueva actividad si aplica
```

## Umbrales Parametrizables

Agregar settings futuros en `social_crm_settings`:

```text
social_pipeline_auto_qualify_recent_score
social_pipeline_auto_appointment_recent_score
social_pipeline_closing_opportunity_threshold
social_pipeline_automation_enabled
```

## Auditoria

Cada movimiento automatico debe registrar:

```text
action = pipeline_stage_changed
performed_by = null
notes = Movido automaticamente por scoring reciente.
external_response = {
  "source": "pipeline_automation",
  "from": "new",
  "to": "qualified",
  "recent_engagement_score": 72,
  "last_engagement_event_type": "video_complete"
}
```

## Integracion Con Eventos

Puntos de entrada:

1. `SocialSmartLinkController::track()` despues de recalcular `recent_engagement_score`.
2. `WhatsappSalesAgentService` despues de detectar intencion.
3. Comando programado para revisar leads no procesados.

## Tests Sugeridos

1. Lead nuevo con score reciente alto pasa a calificado.
2. Lead con `whatsapp_click` pasa a cita.
3. Lead perdido no cambia de etapa.
4. Movimiento automatico crea accion de auditoria.
5. Umbral desactivado no mueve leads.
