# Patient Flow Management para OdonCRM

## Objetivo

Evolucionar el modulo actual de citas de OdonCRM hacia un sistema de Patient Flow Management. El objetivo no es solo administrar agenda, sino optimizar el flujo completo del paciente desde que agenda una cita hasta que finaliza su atencion.

El sistema debe ayudar a reducir tiempos de espera, disminuir ausencias, mejorar la experiencia del paciente y entregar informacion operativa en tiempo real para recepcionistas, doctores y administradores.

Este modulo debe entenderse como una expansion de OdonCRM hacia Operacion Clinica, no como reemplazo del CRM ni como historia clinica completa.

Flujo conceptual:

```text
CRM / Pipeline -> Cita -> Patient Flow -> Metricas operativas -> Seguimiento CRM
```

## Limites del Modulo

Patient Flow debe cubrir recepcion, espera, preparacion, consulta, finalizacion y analitica operativa. No debe convertirse en un sistema hospitalario completo ni en historia clinica.

Incluye:

- Check-in.
- Estados operativos de cita.
- Cronometro de espera y consulta.
- Panel de recepcion.
- Cola de asistente.
- Mi cola del doctor.
- Notas operativas.
- Eventos e historial del flujo.
- Dashboard administrativo de operacion clinica.
- Automatizaciones relacionadas con asistencia y recordatorios.

No incluye en MVP:

- Historia clinica formal.
- Diagnosticos clinicos.
- Consentimientos complejos.
- Facturacion medica.
- Inventario clinico.
- Gestion avanzada de consultorios.
- Tratamientos clinicos profundos.
- Modulo hospitalario HIS/EMR.

Regla de separacion:

- Pipeline gestiona oportunidades y conversion comercial.
- Citas gestiona agenda y administracion de citas.
- Patient Flow gestiona operacion del dia.
- Administracion ve analitica y configuracion, no opera pacientes en vivo.

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

Para ejecucion inicial, conviene separar estados persistidos de estados derivados. No todo lo que se ve en la interfaz debe guardarse como estado real en base de datos.

Estados persistidos recomendados:

- `pending_confirmation`: Pendiente de confirmacion.
- `confirmed`: Confirmada.
- `checked_in`: En espera.
- `preparing`: En preparacion.
- `ready_for_doctor`: Listo para doctor.
- `in_consultation`: En consulta.
- `completed`: Finalizada.
- `cancelled`: Cancelada.
- `rescheduled`: Reprogramada.
- `no_show`: No Show.

Estados derivados para UI y alertas:

- `Por llegar`: cita de hoy pendiente o confirmada, sin check-in.
- `Retrasado`: cita cuya hora programada ya paso y no tiene check-in.
- `Espera critica`: cita en espera que supera el umbral configurado.
- `Listo demorado`: paciente listo para doctor hace mas de N minutos.

Estados futuros:

- `on_the_way`: En camino. Debe reservarse para una fase posterior con WhatsApp/Pity Voice, cuando el paciente confirme que viene en camino.

Notas:

- No persistir `Por llegar` ni `Retrasado` como estados reales en MVP.
- `ready_for_doctor` permite separar llegada/preparacion de disponibilidad real para consulta.
- Cada cambio de estado persistido debe registrarse en historial.

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
- `checked_in_at`
- `preparation_started_at`
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

Tabla nueva recomendada para MVP:

- `appointment_events`: historial de transiciones y eventos.

Tablas futuras, no obligatorias para MVP:

- `appointment_check_ins`: intentos y detalles de check-in por canal.
- `appointment_reminders`: recordatorios enviados y respuestas.
- `appointment_notes`: notas operativas si se decide separarlas de eventos.

Regla de alcance:

- No crear tablas futuras hasta que exista un caso de uso implementado que las necesite.
- Para MVP, priorizar `appointments` + `appointment_events` y, si hace falta, notas simples asociadas a la cita.

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
2. Acciones manuales internas: preparar, listo para doctor, iniciar consulta y finalizar consulta.
3. Paneles internos: recepcion, asistente y doctor.
4. QR en recepcion.
5. WhatsApp.
6. Pity Voice.
7. NFC o geolocalizacion.

Regla de prioridad:

- Primero estabilizar el flujo manual interno.
- Despues agregar canales externos como QR, WhatsApp y Pity Voice.

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

## Notas Operativas por Paciente y Cita

Las notas operativas son observaciones internas del flujo de atencion. Deben ayudar a recepcion, asistentes y doctores a coordinar mejor la experiencia del paciente durante una cita especifica.

No deben reemplazar la historia clinica ni usarse como diagnostico formal.

Ejemplos de notas utiles:

- `Llego con acompanante.`
- `Paciente refiere ansiedad, aplicar comunicacion calmada.`
- `Pidio ser atendido lo antes posible.`
- `Tiene dolor fuerte.`
- `Primera vez en la clinica.`
- `Solicita explicacion detallada del procedimiento.`
- `Paciente molesto por espera anterior.`

Regla principal:

- La nota debe asociarse primero a la cita (`appointment`), no solo al paciente.
- Si una observacion debe quedar como informacion permanente, luego puede convertirse en nota del contacto/paciente.

Por que asociarla a la cita:

- `Llego con acompanante` aplica a esta visita.
- `Paciente ansioso` puede ser relevante para esta atencion.
- `Molesto por espera` corresponde a un evento puntual.
- Permite trazabilidad por fecha, rol y usuario.

### Modelo sugerido

Para MVP puede bastar con registrar notas como eventos o una tabla simple.

Opcion MVP:

- `appointment_id`.
- `created_by`.
- `note`.
- `created_at`.

Opcion robusta futura: tabla `appointment_notes`.

Campos sugeridos:

- `id`.
- `appointment_id`.
- `patient_id`.
- `created_by`.
- `visibility`.
- `note_type`.
- `note`.
- `is_pinned`.
- `created_at`.
- `updated_at`.

Tipos sugeridos:

- `operational`: operativa.
- `reception`: recepcion.
- `assistant`: asistencia.
- `doctor`: doctor.
- `clinical_context`: contexto clinico no diagnostico.
- `alert`: alerta.

Visibilidad sugerida:

- `internal`: equipo interno.
- `reception`: recepcion y admin.
- `clinical_team`: doctor, asistente y admin.

### UX por rol

Recepcion:

- Boton `Nota` en el drawer del paciente.
- Puede registrar observaciones de llegada o comportamiento operativo.
- Puede marcar una nota como importante si debe verla el asistente o doctor.

Asistente:

- Debe ver notas relevantes en la card o drawer.
- Debe poder agregar notas de preparacion.
- Ejemplos: `Paciente ansioso`, `Aplicar protocolo suave`, `Requiere acompanante`.

Doctor:

- Debe ver la nota mas relevante en la card principal de `Mi cola`.
- Puede agregar nota operativa o de contexto no diagnostico.
- No debe sobrecargarse con todas las notas en la vista principal.

Admin:

- Puede revisar notas en timeline o auditoria.
- No deben ser foco principal del dashboard administrativo.

### Reglas de redaccion

- Usar lenguaje neutral y profesional.
- Evitar diagnosticos formales en notas operativas.
- Evitar comentarios subjetivos, ofensivos o ambiguos.
- Registrar observaciones accionables.

Ejemplos recomendados:

- `Paciente refiere ansiedad, aplicar comunicacion calmada.`
- `Llego con acompanante.`
- `Solicita explicacion paso a paso.`
- `Movilidad reducida, ofrecer asistencia.`

Ejemplos a evitar:

- `Paciente dificil.`
- `Vino con alguien raro.`
- `Exagerado con el dolor.`

### Funcionalidades recomendadas

- Agregar nota desde drawer.
- Mostrar ultima nota relevante en card.
- Mostrar historial de notas en drawer.
- Registrar autor y hora.
- Marcar nota como importante.
- Plantillas rapidas futuras: ansioso, prioritario, con acompanante, dolor, movilidad reducida, primera visita.

### Implementacion recomendada

- MVP: incluir boton `Nota`, textarea simple, guardado con autor/hora y visualizacion en card/drawer.
- Fase posterior: plantillas, visibilidad avanzada, notas fijadas y conversion a nota permanente del paciente.

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

Debe enfocarse en gestion y analitica operativa. Esta pantalla es nueva y debe agregarse para roles Admin y Super Admin. No reemplaza `Recepcion`, no reemplaza `Citas` y no debe servir para mover pacientes entre estados.

Nombre sugerido:

- `Operacion clinica`.

Objetivo:

- Mostrar como esta funcionando la clinica en el dia.
- Detectar saturacion por doctor.
- Medir tiempos de espera y consulta.
- Identificar no-show, cancelaciones y retrasos.
- Dar visibilidad administrativa para tomar decisiones operativas.

Formato visual sugerido:

- Widgets compactos.
- Cards de indicadores.
- Graficos ApexCharts.
- Tablas solo para auditoria, historico o exportacion.
- Filas resumen tipo card para productividad o saturacion por doctor.
- Botones compactos con icono para `Umbrales` y `Exportar`.
- Estilo limpio tipo Metronic siguiendo `.cursorrules`.
- Reutilizar `mc-card`, `mc-badge`, `mc-btn`, `mc-facts` y patrones existentes cuando aplique.

Mini cards superiores:

- `Agendadas`.
- `Confirmadas`.
- `Atendidas`.
- `Canceladas`.
- `No Show`.
- `Espera prom`.
- `Consulta prom`.
- `Puntualidad`.

Agrupacion conceptual:

- Volumen: agendadas, confirmadas, atendidas.
- Incidencias: canceladas, no-show.
- Tiempos: espera promedio, consulta promedio, puntualidad.

Secciones principales:

- `Estado actual de la clinica`: distribucion en vivo por estado operativo.
- `Alertas operativas`: eventos que requieren atencion administrativa.
- `Productividad y saturacion por doctor`: carga, atendidas, pacientes en espera y saturacion.

Estado actual de la clinica:

- En espera.
- En preparacion.
- Listo para doctor.
- En consulta.
- Finalizada.

Alertas operativas sugeridas:

- Doctor con varios pacientes acumulados.
- Tiempo promedio de espera por encima del umbral.
- Posible no-show sin confirmar.
- Paciente listo para doctor hace mas de N minutos.
- Paciente en espera hace mas de N minutos.

Productividad y saturacion por doctor:

- Doctor.
- Citas del dia.
- Citas atendidas.
- Pacientes en espera.
- Saturacion estimada.
- Futuro: espera promedio por doctor.
- Futuro: consulta promedio por doctor.

Acciones administrativas:

- `Umbrales`: configurar limites de espera, retraso, no-show y saturacion.
- `Exportar`: descargar reporte del periodo.
- Filtros futuros: hoy, semana, mes, doctor, sede.

Restricciones:

- No debe permitir mover pacientes entre estados.
- No debe reemplazar el panel `Recepcion`.
- No debe convertirse en una tabla densa.
- No debe mostrar datos clinicos sensibles innecesarios.
- Solo Admin y Super Admin deben ver exportacion y configuracion de umbrales.

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
- `patient_flow_admin.view`: ver dashboard administrativo de operacion clinica.
- `appointment_flow.manage`: ejecutar transiciones operativas permitidas.
- `appointment_notes.manage`: crear notas operativas.
- `appointment_metrics.view`: ver metricas operativas.
- `appointment_events.view`: ver historial de eventos.

Permisos finos futuros si se necesita mas control:

- `appointment_check_in.manage`: realizar check-in.
- `appointment_consultation.manage`: iniciar y finalizar consulta.
- `appointment_status_transition.manage`: cambiar estados operativos.

Asignacion recomendada:

- Super Admin: todos los permisos.
- Admin: todos los permisos operativos y analiticos.
- Recepcionista: `patient_flow_reception.view`, `appointment_flow.manage` limitado, `appointment_notes.manage`, `appointment_events.view` limitado.
- Doctor: `patient_flow_doctor.view`, `appointment_flow.manage` limitado, `appointment_notes.manage`, `appointment_events.view` limitado.
- Asistente: `patient_flow_assistant.view`, `appointment_flow.manage` limitado, `appointment_notes.manage`, `appointment_events.view` limitado.

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

- Futuro: preguntar si el paciente viene en camino.
- Futuro: marcar `on_the_way` si confirma.
- Alertar a recepcion si el paciente llega tarde.
- Avisar al paciente si la clinica tiene retraso.

Durante la llegada:

- Check-in por QR, WhatsApp o recepcion.
- Notificacion a recepcion.
- Notificacion al doctor cuando el paciente este listo.

Despues de la atencion:

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
- Jobs y queues para WhatsApp, Pity Voice y recordatorios.
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

### Fase 2: Flujo manual interno y cronometro

Objetivo: estabilizar el flujo manual interno antes de agregar QR, WhatsApp o automatizaciones.

Alcance:

1. Agregar accion de recepcion `Check-in`.
2. Guardar `checked_in_at` y `check_in_source`.
3. Agregar accion `Preparar paciente`.
4. Guardar `preparation_started_at`.
5. Agregar accion `Listo para doctor`.
6. Guardar `ready_for_doctor_at`.
7. Agregar accion `Iniciar consulta`.
8. Guardar `consultation_started_at`.
9. Agregar accion `Finalizar consulta`.
10. Guardar `consultation_finished_at` y `completed_at`.
11. Calcular tiempo esperando y tiempo de consulta desde timestamps.
12. Mostrar semaforo de espera en la vista operativa.

Criterio de salida:

- Recepcion puede marcar llegada.
- Asistente puede preparar y marcar listo para doctor.
- Doctor puede iniciar y finalizar consulta.
- El sistema calcula tiempos reales de espera y atencion.

### Fase 3: Panel de recepcion

Objetivo: crear la torre de control diaria de recepcion.

Alcance:

1. Pacientes por llegar.
2. Pacientes en espera.
3. Pacientes retrasados.
4. Pacientes en preparacion.
5. Pacientes listos para doctor.
6. Pacientes en consulta.
7. Semaforo de prioridad.
8. Drawer lateral con transiciones validas.
9. Acciones rapidas por cita.

Criterio de salida:

- Recepcion puede operar el flujo diario sin depender de tablas largas.
- `Por llegar` y `Retrasado` funcionan como estados derivados.
- Las transiciones se ejecutan desde servicios, no desde logica duplicada en la vista.

### Fase 4: Cola clinica del asistente y Mi cola del doctor

Objetivo: crear pantallas operativas enfocadas para asistente y doctor.

Alcance asistente:

1. Cola clinica sin columna `Por llegar`.
2. En espera.
3. En preparacion.
4. Listo para doctor.
5. En consulta.
6. Filtro por doctores asignados.

Alcance doctor:

1. Proximo paciente.
2. Pacientes listos.
3. Pacientes en preparacion o espera como informacion secundaria.
4. Panel de consulta actual.
5. Comunicacion con recepcion/asistente.
6. Botones iniciar/finalizar consulta segun estado.

Criterio de salida:

- Asistente opera preparacion y listo para doctor.
- Doctor tiene una vista enfocada en la siguiente decision clinica.
- Ninguna de estas vistas usa kanban completo para doctor ni tablas administrativas.

### Fase 5: Notas operativas

Objetivo: permitir contexto operativo por paciente y cita.

Alcance:

1. Boton `Nota` desde drawer.
2. Textarea simple.
3. Guardar autor y hora.
4. Mostrar ultima nota relevante en card.
5. Mostrar historial de notas en drawer.
6. Mantener notas como operativas, no historia clinica.

Criterio de salida:

- Recepcion, asistente y doctor pueden compartir contexto operativo.
- Las notas quedan asociadas a la cita.
- Se evita lenguaje clinico/diagnostico formal.

### Fase 6: Dashboard administrativo de operacion clinica

Objetivo: convertir eventos y timestamps en indicadores de gestion para Admin y Super Admin.

Alcance:

1. Mini cards administrativas.
2. Estado actual de la clinica.
3. Alertas operativas.
4. Productividad y saturacion por doctor.
5. Umbrales configurables.
6. Exportacion de reportes.

Criterio de salida:

- Administracion puede detectar cuellos de botella.
- El dashboard no permite mover pacientes entre estados.
- Las metricas se calculan desde eventos y timestamps auditables.

### Fase 7: Check-in QR y WhatsApp

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

### Fase 8: Automatizaciones con WhatsApp

Objetivo: reducir no-shows y mejorar comunicacion operacional.

Alcance:

1. Confirmacion automatica 24 horas antes.
2. Recordatorio 2 horas antes.
3. Mensaje de retraso si la clinica esta atrasada.
4. Seguimiento automatico para no-show o cancelacion.
5. Fase futura: escalamiento a Pity Voice si no responde WhatsApp.

Criterio de salida:

- Las automatizaciones generan eventos trazables.
- El equipo puede ver que se envio, cuando y por que canal.

### Fase 9: Inteligencia predictiva y diferenciadores

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
- Las cards de Patient Flow deben verse como parte del sistema actual, no como una interfaz externa generada desde cero.
- Reutilizar patrones visuales de `Citas` y `Social Inbox`: cards limpias, drawer lateral, badges sobrios, acciones compactas y estructura clara.
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
