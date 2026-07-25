# Tareas - Patient Flow Management

Documento de seguimiento para ejecutar Patient Flow como modulo nuevo de Operacion Clinica dentro de OdonCRM.

Documento base: `docs/CRM/patient-flow-management/00_PROPUESTA.md`.

Estados sugeridos:

- `[ ]` Pendiente.
- `[~]` En progreso.
- `[x]` Completado.
- `[!]` Bloqueado o requiere decision.

## Principios de ejecucion

- No reemplazar `Citas`.
- No duplicar `appointments`.
- No mezclar Pipeline con operacion clinica.
- No construir historia clinica formal en este modulo.
- Primero estabilizar el flujo manual interno.
- Despues agregar QR, WhatsApp, Pity Voice e inteligencia.
- Reutilizar cards, drawers, badges y botones existentes del sistema.
- Seguir `.cursorrules` para UI: estilo Metronic limpio, sobrio, compacto y en espanol.

## Fase 0: Preparacion y compatibilidad

Objetivo: preparar el modulo sin romper la agenda actual.

- [ ] Auditar modelo `Appointment` actual.
- [ ] Auditar enum `AppointmentStatus` actual.
- [ ] Auditar `AppointmentResource` y paginas Filament actuales.
- [ ] Auditar integraciones actuales con Pipeline, WhatsApp y Pity Voice.
- [ ] Definir mapeo de estados actuales a estados Patient Flow.
- [ ] Confirmar que `/admin/appointments` sigue siendo la ruta CRUD de citas.
- [ ] Definir transiciones validas entre estados.
- [ ] Definir permisos MVP de Patient Flow.
- [ ] Documentar decisiones tecnicas antes de migrar.

Criterio de salida:

- [ ] La agenda actual sigue funcionando.
- [ ] Existe mapeo claro de estados.
- [ ] Existe lista final de transiciones validas.
- [ ] No existe modulo paralelo que duplique citas.

## Fase 1: Base operativa del flujo

Objetivo: convertir la cita en una entidad trazable con historial.

- [ ] Actualizar o crear estados persistidos de `AppointmentStatus`.
- [ ] Confirmar estados derivados que no se guardan: `Por llegar`, `Retrasado`, `Espera critica`.
- [ ] Crear migracion para timestamps operativos en `appointments`.
- [ ] Agregar `checked_in_at`.
- [ ] Agregar `preparation_started_at`.
- [ ] Agregar `ready_for_doctor_at`.
- [ ] Agregar `consultation_started_at`.
- [ ] Agregar `consultation_finished_at`.
- [ ] Agregar `completed_at` si no existe.
- [ ] Agregar `cancelled_at` si no existe.
- [ ] Agregar `no_show_at` si no existe.
- [ ] Agregar `check_in_source`.
- [ ] Crear migracion `appointment_events`.
- [ ] Crear modelo `AppointmentEvent`.
- [ ] Crear relaciones `Appointment -> events`.
- [ ] Crear `AppointmentFlowService`.
- [ ] Registrar evento por cada cambio de estado.
- [ ] Agregar pruebas de transiciones validas.
- [ ] Agregar pruebas de transiciones invalidas.

Criterio de salida:

- [ ] Cada cambio de estado queda auditado.
- [ ] La logica de transiciones vive fuera de Filament.
- [ ] Las citas existentes siguen visibles.

## Fase 2: Flujo manual interno y cronometro

Objetivo: estabilizar el flujo manual antes de agregar canales externos.

- [ ] Agregar accion `Check-in`.
- [ ] Guardar `checked_in_at` y `check_in_source`.
- [ ] Agregar accion `Preparar paciente`.
- [ ] Guardar `preparation_started_at`.
- [ ] Agregar accion `Listo para doctor`.
- [ ] Guardar `ready_for_doctor_at`.
- [ ] Agregar accion `Iniciar consulta`.
- [ ] Guardar `consultation_started_at`.
- [ ] Agregar accion `Finalizar consulta`.
- [ ] Guardar `consultation_finished_at` y `completed_at`.
- [ ] Calcular tiempo esperando desde `checked_in_at`.
- [ ] Calcular tiempo de consulta desde `consultation_started_at`.
- [ ] Definir umbral inicial de espera critica.
- [ ] Mostrar semaforo basico de espera.

Criterio de salida:

- [ ] Recepcion puede marcar llegada.
- [ ] Asistente puede preparar y marcar listo.
- [ ] Doctor puede iniciar y finalizar consulta.
- [ ] Tiempos reales se calculan desde timestamps.

## Fase 3: Panel de recepcion

Objetivo: crear torre de control diaria para recepcion.

- [ ] Crear pagina Filament `Recepcion`.
- [ ] Agregar permiso `patient_flow_reception.view`.
- [ ] Mostrar columnas: `Por llegar`, `En espera`, `En preparacion`, `Listo para doctor`, `En consulta`.
- [ ] Implementar `Por llegar` como estado derivado.
- [ ] Implementar `Retrasado` como alerta derivada.
- [ ] Crear cards limpias por paciente reutilizando patrones del sistema.
- [ ] Mostrar avatar/iniciales, paciente, hora, doctor y procedimiento.
- [ ] Mostrar badge de estado.
- [ ] Mostrar tiempo esperando cuando aplique.
- [ ] Agregar alertas operativas.
- [ ] Abrir drawer lateral al hacer clic en card.
- [ ] Mostrar acciones contextuales validas en drawer.
- [ ] Evitar dropdown libre para mover estados.
- [ ] Agregar boton `Abrir cita completa`.
- [ ] Verificar mobile y desktop.

Criterio de salida:

- [ ] Recepcion opera el dia sin usar tabla principal.
- [ ] Las cards usan servicios de flujo para cambiar estado.
- [ ] La UI cumple `.cursorrules`.

## Fase 4: Cola clinica del asistente y Mi cola del doctor

Objetivo: crear pantallas operativas enfocadas por rol.

### Asistente

- [ ] Crear pagina `Cola clinica`.
- [ ] Agregar permiso `patient_flow_assistant.view`.
- [ ] Filtrar pacientes por doctores asignados.
- [ ] Mostrar columnas: `En espera`, `En preparacion`, `Listo para doctor`, `En consulta`.
- [ ] No mostrar columna `Por llegar`.
- [ ] Agregar acciones: preparar, marcar listo, avisar doctor, nota.
- [ ] Mostrar notas relevantes en card/drawer.

### Doctor

- [ ] Crear pagina `Mi cola`.
- [ ] Agregar permiso `patient_flow_doctor.view`.
- [ ] No usar kanban completo.
- [ ] Mostrar card principal de proximo paciente accionable.
- [ ] Priorizar `Listo para doctor` como proximo paciente.
- [ ] Mostrar panel `En consulta`.
- [ ] Mostrar duracion de consulta actual.
- [ ] Mostrar comunicacion: mensaje a recepcion, solicitar asistente.
- [ ] Mostrar accion principal segun estado.
- [ ] Mostrar lista secundaria de pacientes pendientes.

Criterio de salida:

- [ ] Asistente mueve pacientes entre espera, preparacion y listo.
- [ ] Doctor ve la siguiente decision clinica sin administrar kanban.
- [ ] Ambas vistas usan UI limpia del sistema.

## Fase 5: Notas operativas

Objetivo: compartir contexto operativo por paciente y cita.

- [ ] Decidir si MVP guarda notas como eventos o tabla `appointment_notes`.
- [ ] Agregar boton `Nota` en drawer.
- [ ] Crear textarea simple.
- [ ] Guardar autor y hora.
- [ ] Asociar nota a `appointment_id`.
- [ ] Mostrar ultima nota relevante en card.
- [ ] Mostrar historial de notas en drawer.
- [ ] Agregar reglas de redaccion en ayuda o placeholder.
- [ ] Evitar uso como historia clinica formal.

Criterio de salida:

- [ ] Recepcion, asistente y doctor comparten contexto operativo.
- [ ] Las notas quedan trazables.
- [ ] No se registran diagnosticos formales en este modulo.

## Fase 6: Dashboard administrativo de operacion clinica

Objetivo: dar gestion y analitica operativa a Admin y Super Admin.

- [ ] Crear pagina `Operacion clinica` para Admin/Super Admin.
- [ ] Agregar permiso `patient_flow_admin.view`.
- [ ] Agregar mini cards: agendadas, confirmadas, atendidas, canceladas, no-show.
- [ ] Agregar mini cards: espera promedio, consulta promedio, puntualidad.
- [ ] Crear bloque `Estado actual de la clinica`.
- [ ] Crear bloque `Alertas operativas`.
- [ ] Crear bloque `Productividad y saturacion por doctor`.
- [ ] Agregar accion `Umbrales`.
- [ ] Agregar accion `Exportar`.
- [ ] Usar ApexCharts si se agregan graficos reales.
- [ ] Evitar que el dashboard permita mover pacientes.

Criterio de salida:

- [ ] Admin ve metricas operativas del dia.
- [ ] Dashboard no reemplaza Recepcion ni Citas.
- [ ] Metricas salen de timestamps/eventos auditables.

## Fase 7: Check-in QR y WhatsApp

Objetivo: permitir llegada autoservicio desde canales externos.

- [ ] Crear pantalla publica `Confirma tu llegada`.
- [ ] Crear pantalla publica de exito `Gracias`.
- [ ] Crear endpoint seguro para check-in QR.
- [ ] Buscar cita del dia por telefono o codigo.
- [ ] Manejar varias citas encontradas.
- [ ] Manejar cita no encontrada.
- [ ] Manejar llegada ya registrada.
- [ ] Registrar intentos fallidos.
- [ ] Agregar rate limiting.
- [ ] Integrar WhatsApp con intenciones tipo `LLEGUE`.
- [ ] Crear evento al registrar llegada externa.

Criterio de salida:

- [ ] Paciente puede hacer check-in sin recepcion.
- [ ] Recepcion ve el cambio a `En espera`.
- [ ] No se expone informacion sensible en pantalla publica.

## Fase 8: Automatizaciones con WhatsApp y Pity Voice

Objetivo: reducir no-shows y mejorar comunicacion operacional.

- [ ] Confirmacion automatica 24 horas antes.
- [ ] Recordatorio 2 horas antes.
- [ ] Escalamiento a Pity Voice si no responde WhatsApp.
- [ ] Aviso al paciente si la clinica esta atrasada.
- [ ] Solicitud de resena despues de cita finalizada.
- [ ] Seguimiento automatico para no-show.
- [ ] Seguimiento automatico para cancelacion.

Criterio de salida:

- [ ] Las automatizaciones generan eventos trazables.
- [ ] El equipo puede ver que se envio, cuando y por que canal.

## Fase 9: Inteligencia predictiva y diferenciadores

Objetivo: convertir el modulo en sistema inteligente.

- [ ] Score de riesgo de no-show.
- [ ] ETA estimada de atencion.
- [ ] Recomendaciones de reagendamiento.
- [ ] Alertas de saturacion.
- [ ] Deteccion de pacientes con riesgo de mala experiencia.
- [ ] Reporte diario automatico al administrador por WhatsApp.

Criterio de salida:

- [ ] El sistema recomienda acciones, no solo registra eventos.
- [ ] La clinica puede anticiparse a ausencias, retrasos y saturacion.

## Decisiones pendientes

- [!] Definir si notas MVP van en `appointment_events` o tabla `appointment_notes`.
- [!] Definir umbrales iniciales: espera critica, listo demorado, no-show probable.
- [!] Definir si `rescheduled` es estado final o si crea una nueva cita vinculada.
- [!] Definir si `pending_confirmation` se conserva desde citas actuales o se migra.
- [!] Definir alcance exacto de `Exportar` en dashboard admin.

## No hacer en MVP

- [ ] No crear historia clinica formal.
- [ ] No crear modulo HIS/EMR.
- [ ] No crear facturacion medica.
- [ ] No crear inventario clinico.
- [ ] No crear gestion avanzada de consultorios.
- [ ] No agregar QR/WhatsApp antes de estabilizar flujo manual.
- [ ] No duplicar `appointments`.
- [ ] No mezclar Pipeline con check-in o espera.
