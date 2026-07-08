# Fase 2 - WhatsApp Sales Agent

## Objetivo

Convertir el handshake actual de WhatsApp en una conversacion comercial contextual liderada por IA, con tono profesional, empatico y experto.

El agente debe actuar como Coordinadora de Pacientes. Su funcion es filtrar, orientar y calentar el lead para que el equipo humano gestione cierres efectivos.

## Estado Actual

`WhatsappService` ya detecta mensajes con token `DNT-XXXXX` y llama a `SocialConversionService::processIncomingMessage()`.

Actualmente el sistema responde con mensajes fijos:

```text
Gracias. Identificamos tu ficha y daremos seguimiento a tu solicitud.
Gracias. Recibimos tu codigo y el equipo creara o validara tu ficha para continuar.
```

## Cambio Propuesto

Agregar un servicio nuevo:

```text
App\Services\WhatsappSalesAgentService
```

Responsabilidades:

1. Recuperar contexto del lead por `SocialComment`.
2. Leer procedimiento sugerido, post, caption, eventos recientes y score.
3. Construir prompt para IA.
4. Generar respuesta contextual.
5. Detectar intencion de agenda, urgencia o cierre.
6. Devolver JSON estructurado.
7. Registrar resultado en `WhatsappMessage.ai_response` y `social_comment_actions`.

## Contexto Para IA

El agente debe recibir:

```text
Nombre del lead
Red social de origen
Comentario original
Caption del post
Procedimiento sugerido
Token
Eventos Smart Link recientes
recent_engagement_score
interest_score
Estado del pipeline
Historial breve de acciones
Mensaje actual de WhatsApp
```

## Respuesta Esperada De IA

La IA debe responder solo JSON:

```json
{
  "reply": "Hola Maria, vi que te intereso el video de implantes dentales. Te puedo ayudar a coordinar una valoracion para que el especialista revise tu caso y te explique opciones.",
  "intent": "appointment_interest",
  "closing_opportunity_score": 82,
  "requires_human_handoff": true,
  "handoff_reason": "El paciente solicita disponibilidad para cita.",
  "suggested_pipeline_stage": "appointment",
  "clinical_safety_flag": false,
  "appointment_candidate": {
    "wants_appointment": true,
    "preferred_date_text": "manana",
    "preferred_time_text": "tarde"
  }
}
```

## Prompt Base

```text
Eres la Coordinadora de Pacientes de una clinica dental.

Tu tono debe ser profesional, empatico, claro y experto.

No preguntes "En que puedo ayudarte?" si ya existe contexto del lead.
Debes saludar usando el interes detectado:
"Vi que te intereso el video de [Tratamiento]..."

No diagnostiques.
No indiques tratamientos definitivos.
No prometas resultados.
No des precios definitivos.
No reemplaces al odontologo.

Tu objetivo es orientar al paciente y facilitar una valoracion.
Si detectas intencion de agenda, marca requires_human_handoff=true.
Si detectas dolor, sangrado, infeccion, embarazo, medicamento o urgencia, marca clinical_safety_flag=true y requiere humano.

Retorna solo JSON valido.
```

## Intenciones Recomendadas

```text
general_interest
price_question
appointment_interest
ready_to_book
objection_price
objection_time
medical_sensitive
not_interested
unknown
```

## Integracion En WhatsappService

Flujo recomendado:

1. Crear `WhatsappMessage` entrante.
2. Extraer token.
3. Ejecutar handshake social.
4. Si existe `SocialComment`, llamar `WhatsappSalesAgentService`.
5. Enviar `reply` por WhatsApp.
6. Guardar `ai_response`.
7. Si `requires_human_handoff`, crear alerta.
8. Si `appointment_candidate.wants_appointment`, preparar cita o marcar etapa.

## Seguridad Clinica

El agente debe escalar a humano si detecta:

```text
dolor
sangrado
infeccion
embarazo
medicamentos
alergias
trauma
urgencia
diagnostico previo confuso
complicaciones
```

Respuesta segura sugerida:

```text
Gracias por contarnos. Para orientarte de forma responsable, prefiero que nuestro equipo clinico revise tu caso directamente. Te ayudamos a coordinar una valoracion lo antes posible.
```

## Tests Sugeridos

1. Token valido genera respuesta contextual.
2. Mensaje sin token mantiene flujo actual de profesionales.
3. Intencion `ready_to_book` crea alerta de cierre.
4. Tema medico sensible no genera diagnostico y escala a humano.
5. La respuesta queda guardada en `WhatsappMessage.ai_response`.
