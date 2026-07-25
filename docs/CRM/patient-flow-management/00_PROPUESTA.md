# Patient Flow Management para OdonCRM

## Objetivo

Evolucionar el modulo actual de citas de OdonCRM hacia un sistema de Patient Flow Management. El objetivo no es solo administrar agenda, sino optimizar el flujo completo del paciente desde que agenda una cita hasta que finaliza su atencion.

El sistema debe ayudar a reducir tiempos de espera, disminuir ausencias, mejorar la experiencia del paciente y entregar informacion operativa en tiempo real para recepcionistas, doctores y administradores.

## Principio de Implementacion

No se debe reemplazar de golpe el modulo actual de citas.

La recomendacion es evolucionar sobre lo existente:

- Mantener la entidad principal `appointments`.
- Mantener la ruta actual `/admin/appointments`.
- Mantener el recurso actual de Filament para citas.
- Mantener las citas e integraciones existentes.
- Agregar estados, campos operativos, historial de eventos, check-in, dashboards y automatizaciones de forma incremental.

Esto evita romper la agenda actual, WhatsApp, Social CRM, Pity Voice y cualquier integracion relacionada.

## Relacion con Pipeline

El Pipeline se mantiene como modulo independiente.

Separacion recomendada:

- Pipeline: gestiona leads, oportunidades, comentarios sociales, calificacion, seguimiento comercial, WhatsApp y conversion a cita.
- Patient Flow: gestiona lo que ocurre despues de que la cita existe, incluyendo confirmacion, llegada, espera, consulta, finalizacion, cancelacion y no-show.

Flujo esperado:

```text
Lead social -> Pipeline -> Cita creada -> Patient Flow
```

Integraciones necesarias:

- Cuando el Pipeline convierte un lead en cita, esa cita entra al flujo como pre-reservada o pendiente de confirmacion.
- Si la cita finaliza correctamente, el lead puede marcarse como ganado.
- Si la cita termina como no-show, el Pipeline puede reactivar seguimiento.
- Si el paciente cancela o reprograma, el Pipeline debe registrar el evento comercial.

## Flujo de Estados Propuesto

Estados principales:

- `pending_confirmation`: Pendiente de confirmacion.
- `pre_reserved`: Pre-reservada.
- `confirmed`: Confirmada.
- `on_the_way`: En camino.
- `checked_in`: En espera.
- `ready_for_doctor`: Listo para doctor.
- `in_consultation`: En consulta.
- `completed`: Finalizada.

Estados alternativos:

- `cancelled`: Cancelada.
- `rescheduled`: Reprogramada.
- `no_show`: No Show.

Notas:

- `on_the_way` puede ser opcional en la primera version.
- `ready_for_doctor` permite separar llegada del paciente de disponibilidad real para consulta. Por ejemplo, cuando falta completar datos, consentimiento, pago inicial o preparacion previa.
- Cada cambio de estado debe registrarse en historial.

## Historial de Eventos

Cada evento relevante del ciclo de vida de la cita debe guardarse en una tabla `appointment_events`.

Campos sugeridos:

- `id`
- `appointment_id`
- `event_type`
- `from_status`
- `to_status`
- `occurred_at`
- `created_by`
- `source`
- `metadata`
- `created_at`
- `updated_at`

Fuentes sugeridas:

- `reception`
- `doctor`
- `whatsapp`
- `voice`
- `qr`
- `system`
- `automation`

Este historial habilita auditoria, metricas, trazabilidad y analitica sin depender solamente del estado actual de la cita.

## Modelo de Datos

La tabla `appointments` debe conservar el estado actual y los timestamps operativos principales.

Campos sugeridos para `appointments`:

- `status`
- `scheduled_at`
- `confirmed_at`
- `on_the_way_at`
- `checked_in_at`
- `ready_for_doctor_at`
- `consultation_started_at`
- `consultation_finished_at`
- `completed_at`
- `cancelled_at`
- `no_show_at`
- `rescheduled_from_appointment_id`
- `check_in_source`
- `priority_level`
- `delay_reason`
- `room_id` futuro

Tablas nuevas sugeridas:

- `appointment_events`: historial de transiciones y eventos.
- `appointment_check_ins`: intentos y detalles de check-in por canal.
- `appointment_reminders`: recordatorios enviados y respuestas.
- `appointment_feedback_requests`: solicitudes de resena o satisfaccion.

Los tiempos deben calcularse preferiblemente desde timestamps:

- Tiempo de espera: `consultation_started_at - checked_in_at`.
- Tiempo de atencion: `consultation_finished_at - consultation_started_at`.
- Puntualidad del paciente: comparacion entre `checked_in_at` y `scheduled_at`.
- Puntualidad de atencion: comparacion entre `consultation_started_at` y `scheduled_at`.

## Sistema de Check-In

Canales propuestos:

- Boton de recepcion.
- QR en recepcion.
- WhatsApp con respuestas como `LLEGUE`, `YA ESTOY`, `ESTOY EN RECEPCION`.
- Futuro: NFC o geolocalizacion.

Orden recomendado de implementacion:

1. Boton de recepcion.
2. QR en recepcion.
3. WhatsApp.
4. Pity Voice.
5. NFC o geolocalizacion.

Reglas esperadas:

- Al hacer check-in, la cita cambia a `checked_in`.
- Se registra `checked_in_at`.
- Se registra `check_in_source`.
- Se crea evento en `appointment_events`.
- Desde ese momento empieza a medirse el tiempo de espera.

## Pantalla Publica de Check-in QR

Esta pantalla pertenece al flujo externo del paciente. No forma parte del admin de Filament y no debe parecer una tabla o pantalla administrativa.

Uso previsto:

- QR fijo en recepcion por clinica o sede.
- Link enviado por WhatsApp antes de la cita.
- Futuro: token unico por cita.

Ruta sugerida para MVP:

- `/check-in/{clinicSlug}`

Ruta sugerida futura:

- `/check-in/{token}`

### Pantalla inicial: confirmar llegada

Objetivo: permitir que el paciente avise que ya llego a la clinica.

Contenido sugerido:

- Logo o icono de la clinica.
- Nombre de la clinica.
- Titulo: `Confirma tu llegada`.
- Texto: `Ingresa tu telefono o codigo de cita para avisar a recepcion.`
- Campo: `Telefono o codigo`.
- Boton principal: `Ya llegue`.
- Link secundario: `Necesito ayuda de recepcion`.
- Footer: `odonCRM · Patient Flow`.

Comportamiento:

1. Paciente ingresa telefono o codigo.
2. Presiona `Ya llegue`.
3. El sistema busca citas activas del dia.
4. Si encuentra una cita valida, registra check-in.
5. Si hay varias citas posibles, muestra seleccion minima.
6. Si no encuentra cita, muestra estado de ayuda.

Validaciones:

- No mostrar informacion sensible antes de validar.
- Aplicar rate limiting para evitar abuso.
- Registrar intentos fallidos.
- Permitir telefonos en formato local o internacional.

### Pantalla de exito: llegada confirmada

Objetivo: confirmar al paciente que recepcion fue notificada y que debe esperar.

Contenido sugerido:

- Icono de confirmacion.
- Titulo: `Gracias`.
- Texto: `Recepcion fue notificada de tu llegada.`
- Estado: `En espera`.
- Actualizado: `justo ahora`.
- Instruccion: `Toma asiento, en breve seras llamado.`

Efectos en el sistema:

- La cita cambia a `checked_in` / `En espera`.
- Se guarda `checked_in_at`.
- Se guarda `check_in_source = qr` o `public_check_in`.
- Se crea evento `checked_in` en `appointment_events`.
- El paciente aparece en el Panel de Recepcion como `En espera`.
- El asistente lo ve si pertenece a sus doctores asignados.
- El doctor lo ve como paciente esperando o pendiente de preparacion.
- Administracion puede medir puntualidad y tiempo de espera.

Informacion opcional:

- Hora de cita.
- Primer nombre o iniciales.
- Doctor asignado.

Informacion que debe evitarse en pantalla publica:

- Nombre completo si no es necesario.
- Telefono.
- Procedimiento clinico detallado.
- Datos sensibles del paciente.

### Estados alternativos

`Cita no encontrada`:

- Mensaje: `No encontramos una cita para hoy con ese dato.`
- Accion: `Necesito ayuda de recepcion`.

`Llegada ya registrada`:

- Mensaje: `Tu llegada ya fue confirmada anteriormente.`
- Estado: `En espera`.
- Mostrar hora real de registro si aplica.

`Llegaste muy temprano`:

- Mensaje: `Tu cita esta programada para mas tarde.`
- Accion posible: `Confirmar llegada de todas formas` o `Hablar con recepcion`.

`Cita cancelada o reprogramada`:

- Mensaje: `Esta cita no esta disponible para check-in.`
- Accion: `Necesito ayuda`.

`Varias citas encontradas`:

- Mostrar seleccion minima con hora y doctor.
- Evitar mostrar datos clinicos sensibles.

`Error temporal`:

- Mensaje: `No pudimos registrar tu llegada. Intenta de nuevo o acercate a recepcion.`

### Regla visual

- Diseno mobile-first.
- Fondo claro.
- Card centrada.
- Boton principal claro y grande.
- Sin navegacion admin.
- Sin tabla.
- Estilo sobrio y limpio alineado con `.cursorrules`.
- Texto simple para pacientes.

## Diferencia entre Citas y Panel de Recepcion

`Citas` y `Panel de Recepcion` no deben ser la misma pantalla.

Ambas usan la misma fuente de datos principal, `appointments`, pero tienen objetivos distintos.

### Citas

Objetivo: administrar la agenda y la informacion completa de las citas.

Uso principal:

- Crear cita.
- Editar cita.
- Buscar citas.
- Reprogramar.
- Revisar citas pasadas o futuras.
- Ver historial completo.
- Administrar datos generales de la cita.

Tipo de pantalla:

- CRUD administrativo.
- Listado filtrable.
- Vista completa por registro.

Ejemplos:

- Crear una cita para manana.
- Buscar una cita de hace dos semanas.
- Editar doctor, paciente, fecha o procedimiento.
- Revisar historial de cambios de una cita.

### Panel de Recepcion

Objetivo: operar el flujo del dia en tiempo real.

Uso principal:

- Ver pacientes por llegar.
- Ver pacientes confirmados.
- Ver pacientes que ya llegaron.
- Ver pacientes en espera.
- Ver cuanto tiempo lleva esperando cada paciente.
- Hacer check-in rapido.
- Avisar al doctor o asistente.
- Marcar no-show.
- Ver semaforo de prioridad.

Tipo de pantalla:

- Torre de control diaria.
- Vista operativa, no CRUD.
- Orientada a acciones rapidas.
- No debe ser una tabla administrativa.
- Debe usar cards, columnas, paneles compactos o split view.

Ejemplos:

- El paciente llego y recepcion hace check-in.
- Recepcion ve que un paciente lleva 25 minutos esperando.
- Recepcion avisa al asistente que el paciente esta listo.
- Recepcion marca no-show cuando el paciente no llego.

### Regla de diseno

- `Citas` administra la agenda.
- `Panel de Recepcion` opera el dia.
- No deben duplicar logica de negocio.
- No deben guardar datos separados.
- Las acciones operativas deben usar servicios compartidos como `AppointmentFlowService` y `AppointmentCheckInService`.
- Las nuevas vistas operativas no deben replicar el formato tabla/listado de `Citas`.
- Las tablas deben quedar reservadas para administracion, busqueda historica o configuracion.
- Las vistas de flujo deben sentirse como interfaces operativas tipo `Social Inbox`: cards compactas, paneles, acciones rapidas y estados visibles.
- Las cards del tablero no deben usar movimiento libre entre columnas como mecanismo principal.
- Al hacer clic en una card, debe abrirse un drawer lateral con contexto y botones de accion validos.

Menu sugerido en `Operacion Clinica`:

- Citas.
- Recepcion.
- Contactos.
- Profesionales.
- Procedimientos.
- Asignaciones.

## Dashboards Operativos

### Recepcion

Debe ser una pantalla operacional, no analitica y no tabular.

Elementos sugeridos:

- Pacientes por llegar.
- Pacientes confirmados.
- Pacientes en espera.
- Pacientes retrasados.
- Pacientes en consulta.
- Semaforo de prioridad.
- Tiempo esperando.
- Botones rapidos: check-in, iniciar consulta, reprogramar, cancelar, marcar no-show.

Formato visual sugerido:

- Columnas por estado operativo.
- Cards compactas por paciente.
- Badge de estado.
- Tiempo esperando destacado.
- Acciones rapidas con icono.
- Panel lateral o detalle expandible para informacion adicional.
- Cambio de estado desde drawer lateral con botones contextuales, no desde dropdown libre.

Drawer lateral sugerido:

- Header con avatar, nombre, telefono y badge de estado.
- Card de datos de cita: hora, duracion, doctor, procedimiento, canal de confirmacion y tiempo esperando.
- Nota de recepcion.
- Bloque `Siguiente paso` con transiciones validas.
- Bloque `Acciones` con avisar, nota, reprogramar, cancelar, no-show o abrir cita completa.
- Footer sticky con acciones frecuentes.
- Boton para abrir la cita completa en el CRUD de `Citas`.

Regla de acciones:

- No mostrar todos los estados como botones siempre.
- No mostrar el estado actual como accion principal.
- Mostrar solo transiciones validas segun el estado actual.
- Usar boton primario para la accion recomendada.
- Usar botones outline/neutros para acciones secundarias.

Acciones sugeridas por estado:

- `Por llegar`: check-in, reprogramar, cancelar, no-show, avisar.
- `En espera`: preparar paciente, listo para doctor, reprogramar, cancelar, avisar, nota.
- `En preparacion`: listo para doctor, volver a espera, avisar doctor, nota.
- `Listo para doctor`: iniciar consulta, volver a preparacion, avisar doctor, nota.
- `En consulta`: finalizar consulta, nota, abrir cita.

Semaforo recomendado:

- Verde: espera normal.
- Amarillo: espera superior al umbral.
- Rojo: espera critica.
- Azul o morado: paciente prioritario.

### Doctor

Debe ser una pantalla simple, directa y enfocada en la siguiente decision clinica. No debe usar kanban completo.

Elementos sugeridos:

- Proximo paciente.
- Pacientes esperando por el doctor.
- Tiempo de espera.
- Procedimiento o motivo de cita.
- Historial breve del paciente.
- Boton iniciar consulta.
- Boton finalizar consulta.
- Notas rapidas.

Formato visual sugerido:

- Card principal de proximo paciente.
- Lista compacta de pacientes esperando.
- Sin tabla principal.
- Acciones rapidas con icono.
- Detalle breve del paciente sin sobrecargar la pantalla.
- KPIs compactos superiores: en espera, preparando, listos, en consulta.
- Panel lateral o card secundaria de paciente en consulta.
- Bloque de comunicacion con recepcion y asistente.

Nombre sugerido de menu:

- `Mi cola`.

Layout recomendado:

- Header: `Mi cola de atencion` con nombre del doctor.
- KPIs compactos: `En espera`, `Preparando`, `Listos`, `En consulta`.
- Card principal: proximo paciente accionable.
- Panel `En consulta`: paciente actualmente atendido y accion para finalizar.
- Panel `Comunicacion`: mensaje a recepcion y solicitar asistente.
- Seccion inferior: pacientes pendientes o cola de espera.

Regla para elegir el proximo paciente:

1. Primero pacientes `Listo para doctor`.
2. Si no hay listos, pacientes `En preparacion` mas cercanos o con mayor espera.
3. Si no hay preparacion, pacientes `En espera` con mayor prioridad o mayor tiempo esperando.

Accion principal segun estado:

- Si el paciente esta `Listo para doctor`, mostrar accion primaria `Iniciar consulta`.
- Si el paciente esta `En preparacion`, mostrar accion primaria `Solicitar actualizacion` o `Solicitar asistente`.
- Si el paciente esta `En espera`, mostrar accion primaria `Solicitar preparacion`.
- Si el paciente esta `En consulta`, mostrar accion primaria `Finalizar consulta` en el panel de consulta actual.

Reglas de la card principal:

- No llamar simplemente `Proximo paciente` si el paciente aun no esta listo.
- Para `En preparacion`, usar titulo como `Proximo en preparacion` o mostrar claramente el badge `En preparacion`.
- Para `En espera`, usar titulo como `Paciente esperando` o indicar que falta preparacion.
- Mostrar nombre, hora de cita, procedimiento, tiempo esperando y nota relevante.
- Mantener `Ver contacto` y `Nota` como acciones secundarias.

Panel `En consulta`:

- Mostrar paciente actual.
- Mostrar procedimiento.
- Mostrar hora de inicio.
- Mostrar duracion transcurrida.
- Boton principal `Finalizar consulta`.
- Si no hay paciente en consulta, mostrar empty state compacto.

Comunicacion:

- `Mensaje a recepcion`.
- `Solicitar asistente`.
- Futuro: `Reportar retraso` si el doctor necesita avisar demora.

### Administracion

Debe enfocarse en indicadores operativos diarios e historicos.

Formato visual sugerido:

- Widgets compactos.
- Cards de indicadores.
- Graficos ApexCharts.
- Tablas solo para auditoria, historico o exportacion.

Indicadores sugeridos:

- Citas agendadas.
- Citas confirmadas.
- Citas atendidas.
- Cancelaciones.
- Reprogramaciones.
- No Show.
- Tiempo promedio de espera.
- Tiempo promedio de consulta.
- Retraso promedio por doctor.
- Puntualidad.
- Productividad por doctor.
- Utilizacion de consultorios.
- Tasa de conversion de lead a cita atendida.

Metricas avanzadas:

- No-show por canal de origen.
- No-show por horario.
- No-show por doctor.
- Tiempo de espera por dia de semana.
- Pacientes con espera superior a umbral.
- Relacion entre espera y satisfaccion/resenas.

## Visibilidad por Rol

El modulo debe dar visibilidad distinta segun el rol. La recepcion necesita una vision completa del flujo diario, mientras que doctor y asistente necesitan una vista enfocada en los pacientes que requieren accion.

### Recepcionista

Objetivo: coordinar la operacion diaria y comunicar al asistente o doctor lo que esta pasando.

Pantallas sugeridas:

- Citas.
- Contactos.
- Panel de Recepcion.
- Pacientes en espera.
- Check-in.

Informacion visible:

- Pacientes por llegar.
- Pacientes confirmados.
- Pacientes que hicieron check-in.
- Pacientes en espera.
- Tiempo esperando por paciente.
- Doctor asignado.
- Procedimiento o motivo.
- Estado actual.
- Semaforo de prioridad.
- Pacientes retrasados.
- Pacientes no presentados.

Acciones sugeridas:

- Marcar check-in.
- Avisar al doctor.
- Avisar al asistente.
- Reprogramar.
- Cancelar.
- Marcar no-show.
- Registrar nota operativa.

### Doctor

Objetivo: ver su cola de atencion y actuar rapidamente sobre el siguiente paciente.

Pantallas sugeridas:

- Mi agenda.
- Pacientes esperando.
- Proximo paciente.

Informacion visible:

- Pacientes asignados al doctor.
- Tiempo esperando.
- Hora agendada.
- Motivo o procedimiento.
- Estado actual.
- Notas relevantes del paciente.
- Si el paciente esta listo para consulta.

Acciones sugeridas:

- Iniciar consulta.
- Finalizar consulta.
- Solicitar preparacion.
- Registrar nota clinica u operativa basica.

Restriccion recomendada:

- El doctor no necesita ver toda la operacion de la clinica, solo su agenda y su cola.

### Asistente

Objetivo: servir de puente operativo entre recepcion y doctor.

Pantallas sugeridas:

- Citas.
- Contactos.
- Cola clinica.
- Pacientes en espera.

Informacion visible:

- Pacientes en espera.
- Doctor asignado.
- Tiempo esperando.
- Procedimiento.
- Estado de preparacion.
- Pacientes listos para doctor.

Acciones sugeridas:

- Preparar paciente.
- Marcar listo para doctor.
- Avisar al doctor.
- Registrar nota operativa.

Restriccion recomendada:

- El asistente debe ver pacientes relacionados con los doctores a los que esta asignado, no necesariamente toda la clinica.

### Admin y Super Admin

Objetivo: tener visibilidad total operativa y analitica.

Pantallas sugeridas:

- Todas las pantallas de Patient Flow.
- Dashboard administrativo.
- Reportes historicos.
- Configuracion de umbrales y reglas.

Informacion visible:

- Toda la agenda.
- Todas las colas.
- Metricas operativas.
- Productividad por doctor.
- No-show.
- Puntualidad.
- Tiempos de espera y atencion.

Acciones sugeridas:

- Configurar reglas.
- Ver reportes.
- Auditar eventos.
- Ajustar permisos.
- Corregir estados si existe un error operativo.

## Permisos Sugeridos

Permisos nuevos posibles:

- `patient_flow_reception.view`: ver panel operativo de recepcion.
- `patient_flow_doctor.view`: ver cola del doctor.
- `patient_flow_assistant.view`: ver cola clinica del asistente.
- `appointment_check_in.manage`: realizar check-in.
- `appointment_consultation.manage`: iniciar y finalizar consulta.
- `appointment_status_transition.manage`: cambiar estados operativos.
- `appointment_metrics.view`: ver metricas operativas.
- `appointment_events.view`: ver historial de eventos.

Asignacion recomendada:

- Super Admin: todos los permisos.
- Admin: todos los permisos operativos y analiticos.
- Recepcionista: `patient_flow_reception.view`, `appointment_check_in.manage`, `appointment_status_transition.manage` limitado, `appointment_events.view` limitado.
- Doctor: `patient_flow_doctor.view`, `appointment_consultation.manage`, `appointment_events.view` limitado.
- Asistente: `patient_flow_assistant.view`, `appointment_status_transition.manage` limitado, `appointment_events.view` limitado.

Notas:

- Recepcion debe tener la mejor vision del flujo diario completo.
- Doctor debe ver quien esta esperando y cuanto tiempo lleva, pero filtrado a sus pacientes.
- Asistente debe ver quien esta esperando y ayudar a preparar o mover el paciente a listo para doctor.
- Admin debe ver tanto operacion en vivo como analitica historica.

## Automatizaciones

Antes de la cita:

- Confirmacion automatica 24 horas antes.
- Recordatorio 2 horas antes.
- Confirmacion por WhatsApp.
- Llamada con Pity Voice si no responde WhatsApp.
- Instrucciones previas segun procedimiento.

Durante el dia:

- Preguntar si el paciente viene en camino.
- Marcar `on_the_way` si confirma.
- Alertar a recepcion si el paciente llega tarde.
- Avisar al paciente si la clinica tiene retraso.

Durante la llegada:

- Check-in por QR, WhatsApp o recepcion.
- Notificacion a recepcion.
- Notificacion al doctor cuando el paciente este listo.

Despues de la atencion:

- Solicitud de resena.
- Encuesta rapida de satisfaccion.
- Seguimiento si no asistio.
- Reagendamiento si cancelo o fue no-show.

## Arquitectura Recomendada

Separar la logica operativa fuera de Filament.

Componentes sugeridos:

- `AppointmentStatus`: enum de estados.
- `AppointmentEvent`: modelo para historial.
- `AppointmentFlowService`: valida y ejecuta transiciones de estado.
- `AppointmentCheckInService`: procesa check-in desde recepcion, QR, WhatsApp o voz.
- `AppointmentMetricsService`: calcula tiempos e indicadores.
- `AppointmentAutomationService`: coordina recordatorios, avisos y acciones automaticas.
- Jobs y queues para WhatsApp, Pity Voice, recordatorios y solicitudes de resena.
- Policies o permisos por rol para acciones sensibles.

Filament debe funcionar como interfaz. La logica de negocio debe estar en servicios reutilizables.

## UX Recomendada

Principios:

- Reducir clics.
- Usar botones grandes y claros.
- Mostrar solo la informacion necesaria por rol.
- Priorizar acciones operativas sobre tablas largas.
- Evitar que el personal tenga que interpretar estados complejos.

Recepcion necesita velocidad. Doctor necesita foco. Administracion necesita indicadores.

## Mejores Practicas de Otras Industrias

Aerolineas:

- Check-in anticipado.
- Estados claros del viaje.
- Notificaciones proactivas.
- Manejo de retrasos.

Bancos:

- Gestion de colas.
- Priorizacion.
- Tiempo estimado de espera.
- Pantalla operacional para agentes.

Restaurantes:

- Gestion de reservas.
- Lista de espera.
- Confirmacion de asistencia.
- Reasignacion dinamica.

Uber:

- Estado en tiempo real.
- En camino.
- ETA.
- Notificaciones por cambios.

Amazon:

- Eventos trazables.
- Metricas por etapa.
- Automatizacion segun comportamiento.
- Prediccion de riesgo.

## Riesgos y Mitigaciones

Riesgos:

- Demasiados estados pueden confundir al equipo.
- Si recepcion no registra eventos, las metricas pierden valor.
- WhatsApp puede generar falsos positivos con mensajes ambiguos.
- Los doctores pueden olvidar iniciar o finalizar consulta.
- Las metricas pueden percibirse como herramienta de castigo.
- Permisos mal configurados pueden exponer informacion innecesaria.

Mitigaciones:

- UX simple por rol.
- Estados automatizados cuando sea posible.
- Historial auditable.
- Validaciones de transicion.
- Capacitacion corta.
- Metricas orientadas a mejora operativa, no a sancion.
- Permisos claros por rol.

## Funcionalidades Diferenciales

Ideas para diferenciar OdonCRM en Latinoamerica:

- Prediccion de No Show por paciente, horario, canal y comportamiento.
- Semaforo inteligente de espera.
- Cola dinamica por doctor.
- Reconfirmacion automatica si el paciente no responde.
- Reagendamiento automatico por WhatsApp.
- Estimacion de hora real de atencion.
- Alertas de saturacion para recepcion.
- Paciente prioritario o VIP.
- Deteccion de pacientes molestos por espera.
- Solicitud automatica de resena tras atencion exitosa.
- Reporte diario al administrador por WhatsApp.
- Ranking operativo por sede, doctor o especialidad.
- Pantallas de sala de espera.
- QR publico de llegada.
- Voz IA para confirmar citas en horarios de baja respuesta.

## Fases de Desarrollo

### Fase 0: Preparacion y compatibilidad

Objetivo: preparar el modulo sin romper la agenda actual.

Alcance:

1. Auditar el modelo actual `Appointment`, enum de estados, relaciones, recursos Filament, WhatsApp, Pipeline y Pity Voice.
2. Definir mapeo entre estados actuales y nuevos estados de Patient Flow.
3. Mantener `/admin/appointments` como ruta principal.
4. Definir permisos por rol para recepcion, doctor y administracion.
5. Crear documentacion tecnica de transiciones validas.

Criterio de salida:

- La agenda actual sigue funcionando.
- Existe una decision clara de migracion de estados.
- No hay modulo paralelo que duplique citas.

### Fase 1: Base operativa del flujo

Objetivo: convertir la cita en una entidad trazable con historial.

Alcance:

1. Crear o actualizar `AppointmentStatus` con los nuevos estados.
2. Agregar timestamps operativos en `appointments`.
3. Crear tabla `appointment_events`.
4. Crear modelo `AppointmentEvent`.
5. Crear `AppointmentFlowService` para centralizar transiciones.
6. Registrar evento por cada cambio de estado.
7. Agregar pruebas para transiciones validas e invalidas.

Criterio de salida:

- Cada cambio de estado queda auditado.
- Las transiciones no dependen de logica duplicada en Filament.
- Las citas existentes pueden seguir visualizandose.

### Fase 2: Check-in y cronometro de espera

Objetivo: medir llegada, espera, inicio y fin de atencion.

Alcance:

1. Agregar accion de recepcion `Check-in`.
2. Guardar `checked_in_at` y `check_in_source`.
3. Agregar accion `Iniciar consulta`.
4. Guardar `consultation_started_at`.
5. Agregar accion `Finalizar consulta`.
6. Guardar `consultation_finished_at` y `completed_at`.
7. Calcular tiempo esperando y tiempo de consulta desde timestamps.
8. Mostrar semaforo de espera en la lista o vista operativa.

Criterio de salida:

- Recepcion puede marcar llegada.
- Doctor puede iniciar y finalizar consulta.
- El sistema calcula tiempos reales de espera y atencion.

### Fase 3: Dashboard de recepcion y doctor

Objetivo: crear pantallas operativas simples por rol.

Alcance recepcion:

1. Pacientes por llegar.
2. Pacientes en espera.
3. Pacientes retrasados.
4. Pacientes en consulta.
5. Semaforo de prioridad.
6. Acciones rapidas por cita.

Alcance doctor:

1. Proximo paciente.
2. Pacientes esperando por doctor.
3. Tiempo de espera.
4. Motivo o procedimiento.
5. Botones iniciar/finalizar consulta.

Criterio de salida:

- Recepcion y doctor pueden operar el flujo diario sin depender de tablas largas.
- La interfaz muestra informacion relevante por rol.

### Fase 4: Check-in QR y WhatsApp

Objetivo: permitir llegada autoservicio desde canales externos.

Alcance:

1. Crear endpoint seguro para check-in por QR.
2. Permitir identificar cita del dia por telefono o token.
3. Integrar respuesta WhatsApp tipo `LLEGUE`, `YA ESTOY`, `ESTOY EN RECEPCION`.
4. Manejar casos ambiguos cuando el paciente tiene mas de una cita.
5. Registrar intentos fallidos o ambiguos en eventos.

Criterio de salida:

- Paciente puede registrarse sin intervencion manual.
- Recepcion recibe estado actualizado en tiempo real o casi real.

### Fase 5: Automatizaciones con WhatsApp y Pity Voice

Objetivo: reducir no-shows y mejorar comunicacion operacional.

Alcance:

1. Confirmacion automatica 24 horas antes.
2. Recordatorio 2 horas antes.
3. Escalamiento a Pity Voice si no responde WhatsApp.
4. Mensaje de retraso si la clinica esta atrasada.
5. Solicitud de resena despues de cita finalizada.
6. Seguimiento automatico para no-show o cancelacion.

Criterio de salida:

- Las automatizaciones generan eventos trazables.
- El equipo puede ver que se envio, cuando y por que canal.

### Fase 6: Analitica administrativa

Objetivo: convertir los eventos en indicadores de gestion.

Alcance:

1. Dashboard administrativo diario.
2. Tiempo promedio de espera.
3. Tiempo promedio de consulta.
4. No-show por doctor, horario y canal.
5. Productividad por doctor.
6. Puntualidad.
7. Utilizacion de consultorios cuando exista `room_id`.

Criterio de salida:

- Administracion puede detectar cuellos de botella.
- Las metricas se calculan desde eventos y timestamps auditables.

### Fase 7: Inteligencia predictiva y diferenciadores

Objetivo: posicionar OdonCRM como plataforma inteligente, no solo operativa.

Alcance:

1. Score de riesgo de no-show.
2. ETA estimada de atencion.
3. Recomendaciones de reagendamiento.
4. Alertas de saturacion.
5. Deteccion de pacientes con riesgo de mala experiencia.
6. Reporte diario automatico al administrador por WhatsApp.

Criterio de salida:

- El sistema no solo registra eventos, tambien recomienda acciones.
- La clinica puede anticiparse a ausencias, retrasos y saturacion.

## Reglas de Implementacion y UI

Las nuevas pantallas deben seguir las reglas actuales de `.cursorrules`.

Reglas generales:

- Mantener estetica administrativa limpia tipo Metronic.
- Usar interfaz en espanol.
- Evitar gradientes llamativos, sombras fuertes, efectos decorativos pesados y bordes exageradamente redondos.
- Preferir tarjetas blancas con borde suave, sombras minimas y buen espaciado.
- Mantener tipografia compacta, con pesos normales entre `400` y `600`.
- Evitar textos demasiado grandes en vistas administrativas.

Filament:

- Usar el header nativo de Filament para titulo y subtitulo.
- Usar `getSubheading()` para subtitulos de pagina.
- No duplicar titulos dentro de vistas Blade.
- Mantener acciones con icono usando `->icon()`.
- Mantener botones compactos, sobrios y sin efectos visuales exagerados.

Componentes visuales:

- Reutilizar clases existentes `mc-*` cuando aplique, especialmente `mc-card`, `mc-badge`, `mc-btn`, `mc-btn-soft`, `mc-btn-primary`, `mc-facts` y `mc-grid-main`.
- Para cards usar fondo blanco, borde `#e5e7eb`, radio entre `.625rem` y `.875rem` y sombra minima.
- Para buscadores y filtros seguir el patron actual de `smart-search-wrap` y `smart-channel-trigger`.
- Para modales seguir el patron `smart-modal`.
- Para dashboards usar layouts compactos y funcionales, no visuales decorativos.

Estados y colores:

- Usar colores sobrios para estados: verde para exito, amarillo para alerta, rojo para peligro y gris para neutro.
- Para semaforos de espera mantener una escala clara y consistente.
- En dark mode, toda vista personalizada debe incluir soporte para `.dark`.

Graficos:

- Para nuevos graficos administrativos usar ApexCharts.
- Extender `App\Filament\Widgets\ApexChartWidget`.
- Usar `App\Filament\Widgets\Concerns\HasApexChartDefaults`.
- Preferir graficos compactos: barras, barras horizontales, donut, area o radial bar.
- Mantener colores sobrios tipo Metronic.

Arquitectura de codigo:

- No concentrar reglas de negocio en Resources o Pages de Filament.
- Centralizar transiciones en servicios como `AppointmentFlowService`.
- Centralizar check-in en `AppointmentCheckInService`.
- Centralizar metricas en `AppointmentMetricsService`.
- Registrar eventos de dominio para cada cambio relevante.
- Agregar pruebas para transiciones, calculos de tiempo y automatizaciones.

## Conclusion

La oportunidad principal es convertir a OdonCRM en un sistema operativo para clinicas odontologicas, no solo en una agenda.

La combinacion de WhatsApp, IA, Pity Voice, citas, flujo en tiempo real y analitica accionable puede diferenciar la plataforma frente a soluciones tradicionales del mercado latinoamericano.
