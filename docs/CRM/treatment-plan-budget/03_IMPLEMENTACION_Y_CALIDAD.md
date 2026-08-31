# Implementacion y Calidad - Plan de Tratamiento y Presupuesto

## Estrategia

El modulo se implementara por capacidades productivas sobre una arquitectura comun. Las entregas no deben crear modelos temporales ni guardar informacion estructurada en campos de notas o metadata.

La secuencia prioriza primero consistencia clinica, seguridad y trazabilidad; despues automatizacion financiera y recuperacion avanzada.

## Prerequisitos Tecnicos

### Precios y catalogo

1. Definir la semantica de `procedures.internal_rate`.
2. Incorporar precio comercial separado.
3. Incorporar duracion y sesiones por procedimiento.
4. Definir especialidades y compatibilidad profesional.
5. Corregir consultas de procedimientos que no esten tenant-scoped.

### Agenda

1. Permitir multiples citas futuras por paciente.
2. Centralizar creacion y reprogramacion en un servicio unico.
3. Validar disponibilidad en todos los canales.
4. Implementar reserva atomica para evitar doble booking.
5. Separar cita completada de procedimiento realizado.
6. Incorporar reintentos para sincronizacion con Google Calendar.

### Multitenancy

1. Hacer determinista la instalacion de RLS en bases nuevas.
2. Usar una conexion PostgreSQL que no bypassa RLS.
3. Cambiar RLS a comportamiento fail-closed sin contexto.
4. Establecer contexto en HTTP, Livewire, API, jobs y comandos.
5. Garantizar relaciones dentro de la misma clinica.
6. Evitar cambios de `clinic_id` por mass assignment.

### Seguridad y autorizacion

1. Implementar Policies por registro.
2. Resolver permisos desde la membresia activa de la clinica.
3. Separar permisos de lectura, edicion clinica y edicion comercial.
4. Almacenar tokens publicos como hash.
5. Aplicar rate limiting a endpoints publicos.
6. Verificar firmas de webhooks Meta, WhatsApp y Telnyx.
7. Evitar tokens y datos clinicos sensibles en logs.

### Dominio clinico

1. Separar cita, encuentro clinico y procedimiento realizado.
2. Definir que procedimientos requieren caso especializado.
3. Implementar firma y enmienda sin sobrescritura de registros clinicos.
4. Definir almacenamiento privado para documentos e imagenes.
5. Separar permisos clinicos de permisos comerciales.

## Capacidades De Implementacion

### Capacidad 1: Fundacion de dominio

- Enums de estados y fuentes.
- Migraciones y modelos tenant-aware.
- Relaciones y restricciones.
- Separacion entre items clinicos estables e items de revision inmutables.
- Servicio de transiciones.
- Eventos append-only.
- Policies y permisos.
- Factories y pruebas de aislamiento.

Resultado esperado:

> El sistema puede crear un plan en borrador, agregar items y conservar historial consistente sin exposicion entre clinicas.

### Capacidad 2: Revision y aprobacion

- Calculo server-side de importes.
- Snapshots de procedimiento y precio.
- Revisiones numeradas.
- Flujo de aprobacion.
- Inmutabilidad despues de emision.
- Representacion imprimible/PDF.

Resultado esperado:

> Un cambio de tarifa o catalogo no modifica una revision emitida y cualquier ajuste genera una nueva version auditable.

### Capacidad 3: Filament y ficha del paciente

- Recurso de planes.
- Constructor de items.
- Vista del plan y timeline.
- Panel de planes dentro del paciente.
- Acciones segun permisos.
- Indicadores de avance y valor.

Resultado esperado:

> Doctor, recepcion y administrador ven informacion y acciones acordes con su responsabilidad.

### Capacidad 4: Experiencia publica

- Enlace seguro y revocable.
- Vista responsive con branding de la clinica.
- Registro de visualizaciones.
- Aceptacion completa o parcial.
- Rechazo y solicitud de contacto.
- Evidencia de revision, fecha, IP hash y user agent.
- Descarga del documento presentado.

Resultado esperado:

> El paciente responde sobre una revision exacta sin acceder a datos internos ni de otros tenants.

### Capacidad 5: Agenda y ejecucion

- Relacion muchos a muchos items-citas.
- Seleccion de items al agendar.
- Duracion calculada.
- Reserva atomica.
- Multiples citas futuras.
- Confirmacion de items realizados al finalizar.
- Recalculo de progreso.

Resultado esperado:

> Un tratamiento de varias sesiones puede agendarse y ejecutarse sin perder trazabilidad entre lo propuesto y lo realizado.

### Capacidad 6: Casos y ejecucion clinica

- Caso clinico generico.
- Encuentros asociados a citas.
- Procedimientos realizados con snapshots.
- Firma y enmienda de registros.
- Documentos clinicos privados.
- Extensiones por especialidad.
- Actualizacion trazable del avance del plan.

Resultado esperado:

> Los tratamientos simples se registran sin formularios innecesarios y los tratamientos especializados conservan un expediente longitudinal propio.

### Capacidad 7: Seguimiento operativo

- Cola de planes sin respuesta.
- Cola de items pendientes de decision.
- Cola de items aceptados sin cita.
- Registro de intentos y resultados.
- Proxima accion.
- Envio individual por WhatsApp.
- Consentimiento y opt-out.

Resultado esperado:

> El equipo puede recuperar oportunidades manualmente con contexto completo y sin depender de notas informales.

### Capacidad 8: Analitica

- Valor presupuestado, aceptado, realizado y pendiente.
- Tasa de aceptacion total y parcial.
- Tiempo de respuesta.
- Conversion a cita y realizacion.
- Motivos de rechazo.
- Pendientes por antiguedad.
- Atribucion al CRM social cuando exista origen.

Resultado esperado:

> Los indicadores se calculan desde planes y eventos reales, no desde `estimated_value` como sustituto de ingresos.

### Capacidad 9: Gestion financiera

- Abonos y pagos.
- Asignacion de pagos a plan o items.
- Cuotas y vencimientos.
- Devoluciones.
- Conciliacion y proveedor externo.
- Permisos y auditoria financiera.

Esta capacidad se integra despues de estabilizar revisiones, aceptacion y ejecucion clinica.

### Capacidad 10: Recuperacion inteligente

- Horarios y excepciones por profesional.
- Brechas persistidas.
- Candidatos elegibles.
- Puntaje explicable.
- Ofertas y holds.
- Aceptacion atomica.
- Secuencias controladas.
- Metricas de recuperacion.

Esta capacidad se integra cuando duracion, especialidad, aceptacion y preferencias tengan datos confiables.

## Permisos Recomendados

- `treatment_plans.view_any`
- `treatment_plans.view`
- `treatment_plans.create`
- `treatment_plans.update_clinical`
- `treatment_plans.update_pricing`
- `treatment_plans.apply_discount`
- `treatment_plans.approve`
- `treatment_plans.send`
- `treatment_plans.record_response`
- `treatment_plans.schedule`
- `treatment_plans.complete_item`
- `treatment_plans.cancel`
- `treatment_plans.view_audit`
- `treatment_plans.regenerate_public_link`
- `treatment_plans.export`
- `clinical_cases.view`
- `clinical_cases.create`
- `clinical_cases.update`
- `clinical_cases.close`
- `clinical_encounters.create`
- `clinical_encounters.sign`
- `clinical_encounters.amend`
- `performed_procedures.record`
- `performed_procedures.correct`

Reglas de acceso:

- El doctor accede a planes propios o delegados segun politica.
- Recepcion gestiona contacto y agenda, no modifica diagnostico.
- Los precios y descuentos requieren permisos explicitos.
- Una revision aceptada no puede editarse por ningun rol.
- El superadmin usa bypass explicito y auditado.

## Servicios De Dominio Sugeridos

- `TreatmentPlanService`
- `TreatmentPlanRevisionService`
- `TreatmentPlanPricingService`
- `TreatmentPlanWorkflowService`
- `TreatmentPlanPublicLinkService`
- `TreatmentPlanAcceptanceService`
- `TreatmentPlanSchedulingService`
- `TreatmentPlanCompletionService`
- `TreatmentPlanFollowUpService`
- `ClinicalCaseService`
- `ClinicalEncounterService`
- `PerformedProcedureService`
- `ScheduleGapService`
- `RecoveryCandidateService`
- `RecoveryOfferService`

Las transiciones, calculos y aceptaciones no deben residir directamente en Pages, Resources o Controllers.

## Integraciones

### WhatsApp

- Envio del enlace y revision.
- Plantillas aprobadas fuera de la ventana de servicio.
- Registro de entrega y respuesta.
- Opt-out y limites de contacto.

### Google Calendar

- Crear, actualizar y eliminar eventos desde la cita.
- No usar Google como fuente autoritativa del plan.
- Reintentar fallos mediante jobs idempotentes.

### CRM social

- Conservar atribucion del lead al plan.
- Derivar valor comercial desde revisiones aceptadas.
- Registrar visualizacion y aceptacion como acciones.

### Pity Voice

- Consultar planes autorizados.
- Solicitar contacto o agendar items aceptados.
- No improvisar precios ni explicaciones clinicas.
- Escalar cambios economicos o clinicos a una persona.

## Estrategia De Pruebas

### Unitarias

- Calculo de subtotales, descuentos, impuestos y totales.
- Maquina de estados.
- Elegibilidad de items.
- Puntaje de recuperacion.
- Reglas de vigencia.

### Feature

- Creacion y revision de planes.
- Aprobacion y emision.
- Aceptacion total y parcial.
- Rechazo y solicitud de contacto.
- Conversion a multiples citas.
- Confirmacion de realizacion.
- Creacion y seguimiento de casos clinicos.
- Firma y enmienda de encuentros.
- Registro de procedimientos realizados.
- Permisos por rol.

### Multitenancy

- Lectura y escritura cruzada entre clinicas.
- Relaciones con paciente, procedimiento o profesional de otro tenant.
- Acciones Livewire con IDs de otra clinica.
- Enlaces publicos resueltos desde host incorrecto.
- Jobs y comandos sin contexto.
- Politicas RLS de lectura y escritura.

### Concurrencia

- Dos aceptaciones simultaneas.
- Dos reservas del mismo horario.
- Edicion concurrente de borrador.
- Revision emitida mientras otro usuario edita.
- Dos respuestas sobre una oferta de recuperacion.

### Seguridad

- Token expirado, revocado, invalido o reutilizado.
- Rate limiting.
- CSRF y firma de webhooks.
- Redaccion de tokens en logs.
- Acceso a diagnostico sin permiso.

### End-to-end

```text
Consulta
-> plan
-> revision
-> envio
-> visualizacion
-> aceptacion parcial
-> cita
-> atencion
-> encuentro clinico
-> procedimiento realizado
-> avance
```

## Observabilidad

- Logs estructurados sin datos sensibles innecesarios.
- Correlation ID para envio, respuesta, cita y pago.
- Metricas de errores de emision y aceptacion.
- Metricas de sincronizacion con Google Calendar.
- Cola fallida visible para comunicaciones.
- Alertas por inconsistencias de totales o tenant.
- Auditoria de cambios de precio y descuento.

## Criterios Generales De Calidad

1. PSR-12 y convenciones actuales del proyecto.
2. Enums para estados y fuentes persistentes.
3. `$fillable` y `casts()` explicitos.
4. Servicios transaccionales para reglas de dominio.
5. UI y etiquetas en espanol.
6. PostgreSQL como referencia para concurrencia y RLS.
7. Ningun dato clinico estructurado en notas genericas.
8. Ningun precio historico derivado de un catalogo mutable.
9. Ninguna operacion critica sin autorizacion por registro.
10. Ninguna automatizacion sin trazabilidad e idempotencia.

## Decisiones De Producto Pendientes

1. Significado definitivo de `Ganado` en el pipeline.
2. Modelo de especialidades y compatibilidad profesional.
3. Politica de impuestos por clinica.
4. Limites y aprobacion de descuentos.
5. Nivel de evidencia requerido para aceptacion.
6. Politica de expiracion y reemision.
7. Canales habilitados para seguimiento inicial.
8. Frecuencia maxima y horarios de contacto.
9. Necesidad futura de sillones o recursos clinicos.
10. Proveedor y alcance de pagos cuando se active gestion financiera.
11. Casos especializados que se habilitaran primero.
12. Datos obligatorios y firma para cada tipo de encuentro.
