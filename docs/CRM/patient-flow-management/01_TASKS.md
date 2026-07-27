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

- [x] Auditar modelo `Appointment` actual.
- [x] Auditar enum `AppointmentStatus` actual.
- [x] Auditar `AppointmentResource` y paginas Filament actuales.
- [~] Auditar integraciones actuales con Pipeline, WhatsApp y Pity Voice.
- [ ] Definir mapeo de estados actuales a estados Patient Flow.
- [x] Confirmar que `/admin/appointments` sigue siendo la ruta CRUD de citas.
- [x] Definir transiciones validas entre estados.
- [ ] Definir permisos MVP de Patient Flow.
- [ ] Documentar decisiones tecnicas antes de migrar.

Criterio de salida:

- [x] La agenda actual sigue funcionando.
- [ ] Existe mapeo claro de estados.
- [x] Existe lista final de transiciones validas.
- [ ] No existe modulo paralelo que duplique citas.

## Fase 1: Base operativa del flujo

Objetivo: convertir la cita en una entidad trazable con historial.

- [x] Actualizar o crear estados persistidos de `AppointmentStatus`.
- [x] Confirmar estados derivados que no se guardan: `Por llegar`, `Retrasado`, `Espera critica`.
- [x] Crear migracion para timestamps operativos en `appointments`.
- [x] Agregar `checked_in_at`.
- [x] Agregar `preparation_started_at`.
- [x] Agregar `ready_for_doctor_at`.
- [x] Agregar `consultation_started_at`.
- [x] Agregar `consultation_finished_at`.
- [x] Agregar `completed_at` si no existe.
- [x] Agregar `cancelled_at` si no existe.
- [x] Agregar `no_show_at` si no existe.
- [x] Agregar `check_in_source`.
- [x] Crear migracion `appointment_events`.
- [x] Crear modelo `AppointmentEvent`.
- [x] Crear relaciones `Appointment -> events`.
- [x] Crear `AppointmentFlowService`.
- [x] Registrar evento por cada cambio de estado.
- [x] Agregar pruebas de transiciones validas.
- [x] Agregar pruebas de transiciones invalidas.

Criterio de salida:

- [x] Cada cambio de estado queda auditado.
- [x] La logica de transiciones vive fuera de Filament.
- [x] Las citas existentes siguen visibles.

## Fase 2: Flujo manual interno y cronometro

Objetivo: estabilizar el flujo manual antes de agregar canales externos.

- [x] Agregar accion `Check-in`.
- [x] Guardar `checked_in_at` y `check_in_source`.
- [x] Agregar accion `Preparar paciente`.
- [x] Guardar `preparation_started_at`.
- [x] Agregar accion `Listo para doctor`.
- [x] Guardar `ready_for_doctor_at`.
- [x] Agregar accion `Iniciar consulta`.
- [x] Guardar `consultation_started_at`.
- [x] Agregar accion `Finalizar consulta`.
- [x] Guardar `consultation_finished_at` y `completed_at`.
- [x] Calcular tiempo esperando desde `checked_in_at`.
- [x] Calcular tiempo de consulta desde `consultation_started_at`.
- [x] Definir umbral inicial de espera critica.
- [x] Mostrar semaforo basico de espera.

Criterio de salida:

- [x] Recepcion puede marcar llegada.
- [x] Asistente puede preparar y marcar listo.
- [x] Doctor puede iniciar y finalizar consulta.
- [x] Tiempos reales se calculan desde timestamps.

## Fase 3: Panel de recepcion

Objetivo: crear torre de control diaria para recepcion.

- [x] Crear pagina Filament `Recepcion`.
- [x] Agregar permiso `patient_flow_reception.view`.
- [x] Mostrar columnas: `Por llegar`, `En espera`, `En preparacion`, `Listo para doctor`, `En consulta`.
- [x] Implementar `Por llegar` como estado derivado.
- [x] Implementar `Retrasado` como alerta derivada.
- [x] Crear cards limpias por paciente reutilizando patrones del sistema.
- [x] Mostrar avatar/iniciales, paciente, hora, doctor y procedimiento.
- [x] Mostrar badge de estado.
- [x] Mostrar tiempo esperando cuando aplique.
- [x] Agregar alertas operativas.
- [x] Abrir drawer lateral al hacer clic en card.
- [x] Mostrar acciones contextuales validas en drawer.
- [x] Evitar dropdown libre para mover estados.
- [x] Agregar boton `Abrir cita completa`.
- [~] Verificar mobile y desktop.

Criterio de salida:

- [x] Recepcion opera el dia sin usar tabla principal.
- [x] Las cards usan servicios de flujo para cambiar estado.
- [~] La UI cumple `.cursorrules`.

## Fase 4: Cola clinica del asistente y Mi cola del doctor

Objetivo: crear pantallas operativas enfocadas por rol.

### Asistente

- [x] Crear pagina `Cola clinica`.
- [x] Agregar permiso `patient_flow_assistant.view`.
- [x] Filtrar pacientes por doctores asignados.
- [x] Mostrar columnas: `En espera`, `En preparacion`, `Listo para doctor`, `En consulta`.
- [x] No mostrar columna `Por llegar`.
- [~] Agregar acciones: preparar, marcar listo, avisar doctor, nota.
- [x] Mostrar notas relevantes en card/drawer.

### Doctor

- [x] Crear pagina `Mi cola`.
- [x] Agregar permiso `patient_flow_doctor.view`.
- [x] No usar kanban completo.
- [x] Mostrar card principal de proximo paciente accionable.
- [x] Priorizar `Listo para doctor` como proximo paciente.
- [x] Mostrar panel `En consulta`.
- [x] Mostrar duracion de consulta actual.
- [~] Mostrar comunicacion: mensaje a recepcion, solicitar asistente.
- [x] Mostrar accion principal segun estado.
- [x] Mostrar lista secundaria de pacientes pendientes.

Criterio de salida:

- [x] Asistente mueve pacientes entre espera, preparacion y listo.
- [x] Doctor ve la siguiente decision clinica sin administrar kanban.
- [~] Ambas vistas usan UI limpia del sistema.

## Fase 5: Notas operativas

Objetivo: compartir contexto operativo por paciente y cita.

- [x] Decidir si MVP guarda notas como eventos o tabla `appointment_notes`.
- [x] Agregar boton `Nota` en drawer.
- [x] Crear textarea simple.
- [x] Guardar autor y hora.
- [x] Asociar nota a `appointment_id`.
- [x] Mostrar ultima nota relevante en card.
- [~] Mostrar historial de notas en drawer.
- [x] Agregar reglas de redaccion en ayuda o placeholder.
- [x] Evitar uso como historia clinica formal.

Criterio de salida:

- [x] Recepcion, asistente y doctor comparten contexto operativo.
- [x] Las notas quedan trazables.
- [x] No se registran diagnosticos formales en este modulo.

## Fase 6: Dashboard administrativo de operacion clinica

Objetivo: dar gestion y analitica operativa a Admin y Super Admin.

- [x] Crear pagina `Operacion clinica` para Admin/Super Admin.
- [x] Agregar permiso `patient_flow_admin.view`.
- [x] Agregar mini cards: agendadas, confirmadas, atendidas, canceladas, no-show.
- [x] Agregar mini cards: espera promedio, consulta promedio, puntualidad.
- [x] Crear bloque `Estado actual de la clinica`.
- [x] Crear bloque `Alertas operativas`.
- [x] Crear bloque `Productividad y saturacion por doctor`.
- [~] Agregar accion `Umbrales`.
- [~] Agregar accion `Exportar`.
- [x] Usar ApexCharts si se agregan graficos reales.
- [x] Evitar que el dashboard permita mover pacientes.

Criterio de salida:

- [x] Admin ve metricas operativas del dia.
- [x] Dashboard no reemplaza Recepcion ni Citas.
- [x] Metricas salen de timestamps/eventos auditables.

## Fase 7: Check-in QR y WhatsApp

Objetivo: permitir llegada autoservicio desde canales externos.

- [x] Crear pantalla publica `Confirma tu llegada`.
- [x] Crear pantalla publica de exito `Gracias`.
- [x] Crear endpoint seguro para check-in QR.
- [x] Buscar cita del dia por telefono o codigo.
- [x] Manejar varias citas encontradas.
- [x] Manejar cita no encontrada.
- [x] Manejar llegada ya registrada.
- [~] Registrar intentos fallidos.
- [x] Agregar rate limiting.
- [ ] Integrar WhatsApp con intenciones tipo `LLEGUE`.
- [x] Crear evento al registrar llegada externa.

Criterio de salida:

- [x] Paciente puede hacer check-in sin recepcion.
- [x] Recepcion ve el cambio a `En espera`.
- [x] No se expone informacion sensible en pantalla publica.

## Fase 8: Automatizaciones con WhatsApp

Objetivo: reducir no-shows y mejorar comunicacion operacional.

- [x] Crear parametros de Confirmaciones en Configuracion CRM.
- [x] Activar/desactivar recordatorios por WhatsApp desde Configuracion CRM.
- [x] Mantener WhatsApp desactivado por defecto.
- [x] Parametrizar primer recordatorio en horas antes de la cita.
- [x] Parametrizar segundo recordatorio en horas antes de la cita.
- [x] Parametrizar envio solo para citas sin confirmar.
- [x] Crear tabla `appointment_reminders` para trazabilidad y deduplicacion.
- [x] Crear modelo `AppointmentReminder`.
- [x] Relacionar `Appointment -> reminders`.
- [x] Crear `AppointmentReminderService`.
- [x] Crear comando `appointments:send-reminders`.
- [x] Programar comando cada 5 minutos con `withoutOverlapping`.
- [x] Confirmacion automatica 24 horas antes por WhatsApp cuando el canal este activo.
- [x] Recordatorio 2 horas antes por WhatsApp cuando el canal este activo.
- [ ] Migrar WhatsApp reminder a template aprobado de Meta.
- [ ] Aviso al paciente si la clinica esta atrasada.
- [ ] Solicitud de resena despues de cita finalizada.
- [ ] Seguimiento automatico para no-show.
- [ ] Seguimiento automatico para cancelacion.
- [ ] Fase futura: escalamiento a Pity Voice si no responde WhatsApp.
- [ ] Fase futura: integrar llamada saliente real con proveedor de voz.

Criterio de salida:

- [x] Las automatizaciones generan registros trazables.
- [x] El equipo puede auditar que se intento enviar, cuando y por que canal.
- [~] El envio real por WhatsApp esta integrado end-to-end.

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
