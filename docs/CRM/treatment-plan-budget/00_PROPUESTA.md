# Plan de Tratamiento y Presupuesto para OdonCRM

## Objetivo

Incorporar a OdonCRM un modulo clinico-comercial que convierta el diagnostico del paciente en un plan estructurado, trazable y accionable. El modulo debe conectar procedimientos, precios, aceptacion del paciente, agenda, seguimiento y avance clinico sin reducir el tratamiento a una cotizacion economica.

El nombre funcional recomendado es **Plan de Tratamiento y Presupuesto**.

Flujo conceptual:

```text
Consulta -> Diagnostico -> Plan de tratamiento -> Aceptacion -> Agenda -> Atencion -> Avance
                                      |                         |
                                      +-> Seguimiento           +-> Recuperacion
```

## Vision de Producto

OdonCRM debe permitir conocer en todo momento:

1. Que tratamientos necesita cada paciente.
2. Que procedimientos fueron propuestos, aceptados o rechazados.
3. Que procedimientos aceptados siguen sin cita.
4. Que procedimientos estan agendados, en tratamiento o realizados.
5. Cual es el valor presupuestado, aceptado, realizado y pendiente.
6. Que paciente requiere seguimiento y por que.
7. Que tratamiento aceptado puede ocupar un espacio disponible en la agenda.

El sistema recomienda acciones y candidatos. El equipo conserva la decision final, especialmente cuando exista prioridad clinica, informacion sensible o riesgo de contacto inapropiado.

## Principios

1. La unidad principal es cada procedimiento del plan, no solamente el total economico.
2. El estado comercial debe estar separado del estado clinico-operativo.
3. La prioridad clinica debe prevalecer sobre el valor economico.
4. Los precios enviados o aceptados deben conservarse mediante revisiones inmutables.
5. Un cambio en el catalogo no debe alterar documentos ya emitidos.
6. Toda transicion relevante debe registrar actor, fecha, origen y motivo.
7. El paciente puede aceptar el plan completo o procedimientos individuales.
8. Una cita puede atender varios items y un item puede requerir varias citas.
9. La automatizacion debe respetar consentimiento, horarios, frecuencia y opt-out.
10. Todos los registros deben pertenecer a la clinica activa y aislarse por tenant.
11. OdonCRM opera por clinica; no se modelan sucursales.
12. La arquitectura debe ser productiva, extensible y segura desde su primera entrega.
13. La aceptacion economica no equivale al consentimiento informado clinico.
14. Ningun procedimiento debe ejecutarse sin revisar antecedentes, alertas, requisitos y consentimientos aplicables.
15. El cierre comercial, el cierre administrativo y la resolucion clinica son conceptos diferentes.

## Separacion De Dominios

No se debe crear un plan independiente por cada procedimiento. La estructura recomendada es:

```text
Paciente
├── Evaluaciones y diagnosticos
├── Plan de tratamiento
│   ├── Item: limpieza
│   ├── Item: resina pieza 11
│   └── Item: blanqueamiento
├── Casos clinicos especializados
├── Encuentros clinicos
├── Procedimientos realizados
└── Citas
```

Responsabilidad de cada concepto:

- Plan de tratamiento: define que se propone al paciente.
- Revision de presupuesto: conserva cuanto cuesta y que version fue presentada.
- Item del plan: representa cada procedimiento propuesto y su avance.
- Caso clinico: conserva el seguimiento especializado cuando el tratamiento lo requiere.
- Cita: define cuando sera atendido el paciente.
- Encuentro clinico: registra lo ocurrido durante una atencion.
- Procedimiento realizado: registra que se ejecuto realmente.

Un item simple puede completarse con uno o varios procedimientos realizados sin crear un caso especializado. Endodoncia, ortodoncia y periodoncia pueden abrir casos clinicos con formularios, controles y documentos propios.

## Relacion con OdonCRM Actual

### Componentes reutilizables

- `Patient` como identidad principal del paciente.
- `Professional` como responsable clinico y profesional sugerido.
- `Procedure` como catalogo inicial de servicios.
- `Appointment` como unidad de agenda y atencion.
- `Clinic` como tenant, moneda, zona horaria y configuracion operativa.
- `AppointmentAvailabilityService` para disponibilidad.
- `AppointmentSlotOfferService` para ofertas de horarios.
- `AppointmentWorkflowService` para el ciclo de vida de citas.
- `WhatsappService` para comunicaciones.
- `SocialComment` y el pipeline para atribucion de leads sociales.
- `SocialCommentAction` y `AppointmentEvent` como patrones de auditoria.
- Enlaces publicos de citas como referencia de experiencia externa.
- `SocialRoiService` como base para ampliar indicadores comerciales.

### Conceptos que no deben sobrecargarse

- `Procedure.internal_rate` no debe usarse como precio comercial definitivo.
- `SocialComment.estimated_value` no reemplaza el total de un presupuesto.
- `Appointment.procedure_id` no representa un plan con multiples procedimientos.
- `Appointment.completed` no prueba por si solo que un procedimiento fue realizado.
- `patients.notes`, `appointments.notes` y `metadata` no deben almacenar el plan.
- El estado `Presupuesto` del pipeline no reemplaza una entidad de presupuesto real.

## Alcance Funcional

### Nucleo clinico-comercial

- Crear planes asociados a pacientes.
- Registrar diagnostico general y prioridad clinica.
- Agregar procedimientos independientes.
- Registrar pieza, superficie, cantidad, duracion y sesiones.
- Asignar especialidad y profesional sugerido cuando corresponda.
- Calcular subtotal, descuentos, impuestos y total.
- Emitir revisiones inmutables del plan.
- Gestionar vigencia, aprobacion y cierre.
- Mantener estados comerciales y clinicos separados.
- Organizar tratamientos por fases, secuencia y dependencias.
- Representar alternativas mutuamente excluyentes.
- Bloquear items que no esten clinicamente listos.

### Seguridad clinica y diagnostico

- Registrar antecedentes medicos y odontologicos versionados.
- Registrar alergias, medicamentos, condiciones y alertas.
- Distinguir informacion reportada por el paciente de informacion verificada por el profesional.
- Registrar motivo de consulta, examen, hallazgos y diagnosticos estructurados.
- Relacionar hallazgos, evidencia, diagnosticos e items del plan.
- Exigir revision profesional antes de ejecutar o prescribir.
- Permitir bloqueos y excepciones auditadas.

### Experiencia del paciente

- Generar enlace publico seguro, revocable y con expiracion.
- Mostrar solamente la informacion necesaria.
- Registrar primera y ultima visualizacion.
- Permitir aceptacion completa o parcial.
- Permitir rechazo con motivo opcional.
- Permitir solicitar contacto.
- Permitir solicitar agendamiento.
- Generar representacion imprimible o PDF de la revision emitida.
- Separar aceptacion economica, autorizacion de tratamiento y consentimiento informado.
- Registrar representante, tutor o responsable cuando corresponda.

### Agenda y ejecucion

- Convertir items aceptados en una o varias citas.
- Permitir varias citas futuras para un mismo paciente.
- Relacionar items del plan con citas.
- Revalidar disponibilidad antes de reservar.
- Registrar cantidades o sesiones planificadas y realizadas.
- Actualizar el avance del plan al confirmar la atencion realizada.
- Detectar items aceptados sin cita.
- Crear citas planificadas sin fecha para agrupar items, duracion, proveedor y secuencia.
- Verificar preparacion clinica antes de agendar y nuevamente antes de ejecutar.

### Casos y ejecucion clinica

- Determinar si un item requiere caso clinico especializado.
- Crear casos vinculados al paciente y, cuando corresponda, al item del plan.
- Registrar encuentros clinicos asociados a citas.
- Registrar procedimientos efectivamente realizados.
- Mantener detalles propios de ortodoncia, endodoncia y periodoncia.
- Conservar fotografias, radiografias, documentos y controles relacionados.
- Evitar formularios especializados innecesarios para procedimientos simples.

### Seguimiento

- Registrar ultimo contacto, proxima accion, canal y resultado.
- Separar seguimiento comercial de recuperacion de agenda.
- Mostrar presupuestos enviados sin respuesta.
- Mostrar items propuestos pendientes de decision.
- Mostrar items aceptados pendientes de agendamiento.
- Detener contactos ante rechazo definitivo, opt-out o tratamiento completado.
- Separar seguimiento comercial, seguimiento clinico y recall preventivo.
- Registrar controles postoperatorios, complicaciones y escalamiento.
- Mantener recalls configurables por paciente y tipo de cuidado.

### Recuperacion inteligente

- Detectar espacios liberados por cancelacion o reprogramacion.
- Buscar items aceptados sin cita compatibles con el espacio.
- Comparar duracion, profesional, especialidad, prioridad y preferencias.
- Generar un puntaje explicable.
- Mostrar las razones de cada recomendacion.
- Permitir que el equipo seleccione a quien contactar.
- Registrar oferta, respuesta y resultado de recuperacion.

## Alcance De La Primera Entrega Productiva

La primera entrega debe estabilizar el nucleo clinico-comercial sin introducir simultaneamente toda la complejidad financiera y de automatizacion.

Incluye:

1. Identidad del paciente, representante y autorizaciones de contacto.
2. Antecedentes, alergias, medicamentos, condiciones y alertas clinicas.
3. Examen, hallazgos, diagnosticos y evidencia relacionada.
4. Odontograma y registro clinico longitudinal.
5. Planes, fases, alternativas, dependencias, revisiones e items.
6. Estados de ciclo de vida, comerciales y clinicos separados.
7. Precio comercial, descuentos, impuestos y totales.
8. Aceptacion total o parcial separada del consentimiento informado.
9. Consentimientos versionados aplicables al tratamiento.
10. Enlace publico seguro con verificacion proporcional a la informacion expuesta.
11. Seguimiento manual comercial y clinico estructurado.
12. Citas planificadas, multiples citas futuras y validacion de preparacion clinica.
13. Auditoria, permisos granulares y storage clinico privado.
14. Encuentros firmados, enmiendas y procedimientos realizados.
15. Seguimiento postoperatorio y recall preventivo.
16. Fundacion extensible para casos clinicos especializados.

Se integraran en entregas posteriores, sobre el mismo modelo robusto:

- Pagos, abonos, cuotas, devoluciones y conciliacion.
- Control de sillones u otros recursos clinicos, si la operacion lo requiere.
- Campanas automaticas y secuencias multicanal.
- Recuperacion inteligente y ofertas automaticas de espacios.
- Pity Voice para recuperacion saliente.
- Modelos predictivos.

No se contempla un modulo de sucursales.

## Ejemplo Funcional

```text
Paciente: Maria Torres
Plan: PT-2026-00128
Diagnostico: limpieza, resina en pieza 11 y blanqueamiento
Valor presupuestado: $215

Limpieza:
  Comercial: aceptado
  Operativo: realizado
  Duracion: 30 minutos

Resina pieza 11:
  Comercial: aceptado
  Operativo: sin agendar
  Duracion: 45 minutos

Blanqueamiento:
  Comercial: propuesto
  Operativo: sin agendar
  Duracion: 60 minutos
```

Si OdonCRM detecta un espacio de 45 minutos con un profesional compatible, Maria puede ser recomendada para la resina. El blanqueamiento no debe ofrecerse directamente mientras siga pendiente de aceptacion.

## Indicadores Esperados

- Valor presupuestado.
- Valor aceptado.
- Valor rechazado.
- Valor pendiente de decision.
- Valor aceptado sin agendar.
- Valor realizado.
- Tiempo desde envio hasta visualizacion.
- Tiempo desde visualizacion hasta aceptacion.
- Tasa de aceptacion total y parcial.
- Conversion de item aceptado a cita.
- Conversion de cita a procedimiento realizado.
- Motivos de rechazo.
- Procedimientos pendientes por antiguedad.
- Espacios de agenda recuperados.
- Minutos e ingresos recuperados.

## Limites Del Modulo

- El plan y el presupuesto no sustituyen la historia clinica ni los casos especializados.
- Los detalles especializados se almacenan en casos clinicos, no dentro del item economico.
- No permite que IA emita diagnosticos ni cambie prioridades clinicas.
- No asume que una cita completada equivale automaticamente a tratamiento realizado.
- No envia precios inventados o no aprobados por WhatsApp.
- No contacta automaticamente a pacientes sin consentimiento y reglas configuradas.
- No utiliza el valor economico como unico criterio de recuperacion.

## Documentos Relacionados

- `01_MODELO_DATOS.md`: entidades, revisiones, estados e invariantes.
- `02_FLUJOS_Y_REGLAS.md`: ciclos funcionales y recuperacion.
- `03_IMPLEMENTACION_Y_CALIDAD.md`: estrategia tecnica, seguridad y validacion.
- `04_CASOS_CLINICOS_ESPECIALIZADOS.md`: estructura clinica por tipo de tratamiento.
- `05_ODONTOGRAMA.md`: odontograma, hallazgos, diagnosticos e historial.
- `06_MAPA_MODULOS_ODONCRM.md`: modulos recomendados y orden de construccion.
- `07_VALIDACION_FLUJO_CLINICO.md`: auditoria comparativa y baseline clinico obligatorio.
- `08_CATALOGOS_REGIONALES_Y_FISCALIDAD.md`: pais, moneda, aranceles, impuestos y facturacion.
- `09_ONBOARDING_TENANT.md`: alta minima, provisionamiento, capacidades y prueba completa sin SRI.
