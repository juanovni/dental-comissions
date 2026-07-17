# Fase 7 - Roadmap De Implementacion

## Objetivo

Dividir la implementacion en entregas pequenas, verificables y seguras.

## Sprint 1 - Base De Appointments

Tareas:

1. Crear enum `AppointmentStatus`.
2. Crear modelo `Appointment`.
3. Crear migracion `appointments`.
4. Crear relaciones en `Patient`, `SocialComment`, `SocialIdentity`, `SocialPost` y `Procedure`.
5. Crear factory y tests basicos.

Validacion:

1. Migracion corre en PostgreSQL.
2. Se puede crear cita con origen social.
3. Estados castean correctamente.

## Sprint 2 - Creacion De Citas Desde Lead

Tareas:

1. Crear `AppointmentCreationService`.
2. Crear accion desde lead en Filament.
3. Actualizar `conversion_status = appointment_created`.
4. Registrar accion de auditoria.

Validacion:

1. Lead con token crea cita.
2. Cita conserva `social_comment_id`, `social_identity_id`, `social_post_id`.
3. Kanban refleja etapa de cita.

## Sprint 3 - WhatsApp Sales Agent

Tareas:

1. Crear `WhatsappSalesAgentService`.
2. Agregar prompt JSON.
3. Integrar con `WhatsappService` despues del handshake.
4. Guardar `ai_response`.
5. Enviar respuesta contextual.

Validacion:

1. Token valido genera saludo con tratamiento.
2. Tema medico sensible escala a humano.
3. No se rompe flujo de profesionales que registran procedimientos.

## Sprint 4 - Deteccion De Cierre Y Alertas

Tareas:

1. Crear alerta `closing_opportunity`.
2. Crear evento `ClosingOpportunityDetected`.
3. Crear notification Laravel.
4. Integrar con campana existente.
5. Resolver alerta al marcar contacto o crear cita.

Validacion:

1. IA con `ready_to_book` crea alerta.
2. Broadcast actualiza UI.
3. No duplica alertas abiertas.

## Sprint 5 - Automatizacion Kanban

Tareas:

1. Crear `SocialPipelineAutomationService`.
2. Integrar con tracking Smart Link.
3. Integrar con WhatsApp Sales Agent.
4. Agregar settings de umbrales.
5. Registrar auditoria en acciones.

Validacion:

1. Score alto mueve a calificado.
2. Clic WhatsApp mueve a cita.
3. Ganados/perdidos no se mueven automaticamente.

## Sprint 6 - ROI De Citas

Tareas:

1. Extender `SocialRoiService` con citas.
2. Agregar metricas de citas por post.
3. Agregar leakage de cita sin seguimiento.
4. Mostrar KPIs en dashboard.

Validacion:

1. Reporte muestra citas por contenido.
2. Cita completada se conecta con procedimiento.
3. Leakage detecta oportunidades perdidas.

## Sprint 7 - Integracion Externa Mock

Tareas:

1. Crear contrato `AppointmentCalendarProvider`.
2. Crear provider fake para tests.
3. Crear `AppointmentSyncService`.
4. Crear webhook generico `/webhook/appointments/{provider}`.
5. Probar idempotencia.

Validacion:

1. Cita local se sincroniza con provider fake.
2. Webhook actualiza cita existente.
3. Webhook no duplica eventos.

## Orden Recomendado

1. Appointments primero.
2. Creacion manual desde lead.
3. Agente WhatsApp.
4. Alertas de cierre.
5. Automatizacion Kanban.
6. ROI de citas.
7. Integraciones externas.
8. Agente telefonico Retell AI solo despues de tener servicios de agenda reutilizables y auditoria suficiente.

## Extension - Agente Telefonico Retell AI

La planificacion completa vive en:

```text
docs/CRM/odoncrm-voice-agent-retell/
```

No implementar voz como sistema paralelo. Retell debe consumir endpoints Laravel que reutilicen `AppointmentCreationService`, disponibilidad, pacientes, WhatsApp y auditoria.

## Comandos De Verificacion

Segun entorno disponible:

```bash
make test
```

O directamente:

```bash
php artisan test
```

Para migraciones:

```bash
php artisan migrate
```

## Criterio De Listo

Una fase se considera lista cuando:

1. Tiene migraciones y modelos funcionando.
2. Tiene tests automatizados para el flujo principal.
3. Tiene auditoria en `social_comment_actions` cuando aplica.
4. No rompe Smart Links ni registro WhatsApp de profesionales.
5. Mantiene la regla de oro: IA filtra y calienta, humano decide clinicamente.
