# QA End-to-End - Patient Flow Management

Plan de pruebas para validar Patient Flow Management antes de cerrar el MVP.

Documentos relacionados:

- `docs/CRM/patient-flow-management/00_PROPUESTA.md`
- `docs/CRM/patient-flow-management/01_TASKS.md`

## Objetivo

Validar que los modulos conectados funcionen juntos sin romper Agenda, Pipeline, WhatsApp, Google Calendar ni el flujo operativo de recepcion, asistente y doctor.

Modulos a cubrir:

- Configuracion CRM.
- Pipeline / Social CRM.
- Agenda / Citas.
- WhatsApp Cloud API.
- Google Calendar.
- Check-in publico.
- Recepcion.
- Cola clinica.
- Mi cola de atencion.
- Operacion clinica.
- Permisos por rol.
- Mobile/Desktop.

## Configuracion Base

Antes de probar, configurar:

| Configuracion | Valor QA recomendado | Produccion sugerido |
|---|---:|---:|
| Zona horaria clinica | `America/Guayaquil` | Segun clinica |
| Recordatorios WhatsApp | Activo | Activo cuando Meta este validado |
| Primer recordatorio | `24` horas | `24` horas |
| Segundo recordatorio | `2` horas | `2` horas |
| Solo citas sin confirmar | Activo | Activo |
| Alertar si no responde | Activo | Activo |
| Minutos sin respuesta | `1` | `60` |
| Espera warning | `10` min | Segun operacion |
| Espera critica | `20` min | Segun operacion |
| Listo demorado | `10` min | Segun operacion |
| No-show probable | `15` min | Segun operacion |

Verificar tambien:

- WhatsApp Cloud API con `phone_number_id` y `access_token` activos.
- Google Calendar de clinica conectado.
- Doctor activo con agenda disponible.
- Asistente asignado al doctor.
- Roles y permisos cargados con `RolePermissionSeeder` o configuracion equivalente.

## Datos De Prueba

Crear o confirmar que existan:

- Paciente A con telefono WhatsApp real/controlado.
- Paciente B sin telefono.
- Paciente C con varias citas el mismo dia.
- Doctor activo.
- Asistente activo asignado al doctor.
- Recepcionista.
- Admin.
- Super Admin.
- Procedimientos activos: valoracion dental, limpieza, blanqueamiento.

Crear al menos estas citas para el dia de QA:

| Cita | Estado inicial | Objetivo |
|---|---|---|
| Cita 1 | `scheduled` | Recordatorio y confirmacion WhatsApp |
| Cita 2 | `confirmed` | Check-in publico o WhatsApp |
| Cita 3 | `scheduled` o `confirmed` | Multiple citas del mismo paciente |
| Cita 4 | `cancelled` | Check-in no disponible |
| Cita 5 | `completed` | Check-in no disponible |

## Matriz Por Rol

| Rol | Modulos a probar | Objetivo |
|---|---|---|
| Super Admin | Todos | Validar acceso completo y fallback de permisos |
| Admin | Configuracion CRM, Operacion clinica, Agenda, Pipeline | Validar configuracion, metricas y auditoria |
| Recepcion | Recepcion, Check-in publico, alertas | Gestionar llegada y alertas operativas |
| Asistente | Cola clinica | Preparar paciente y marcar listo para doctor |
| Doctor | Mi cola de atencion | Iniciar/finalizar consulta y revisar notas |
| Paciente | WhatsApp, Check-in publico | Confirmar cita, pedir cambio y hacer check-in |

## Fase 1: Configuracion CRM

### Pasos

1. Entrar como Admin o Super Admin.
2. Abrir `Configuracion CRM`.
3. Guardar la seccion `Confirmaciones`.
4. Guardar la seccion `Patient Flow`.
5. Ejecutar comando de recordatorios.

```bash
docker compose exec -T dental.app php artisan appointments:send-reminders
```

### Validaciones

- Los toggles persisten despues de recargar la pantalla.
- `Recordatorios por WhatsApp` queda activo cuando se guarda.
- Los umbrales de Patient Flow afectan colores y alertas.
- La zona horaria usada por recordatorios coincide con la clinica.
- El comando no devuelve `whatsapp_skipped: 1` si WhatsApp esta activo.

## Fase 2: Pipeline A Cita

### Pasos

1. Crear o recibir lead desde Social CRM/WhatsApp.
2. Llevarlo a intencion de cita.
3. Proponer slots reales.
4. Seleccionar un slot.
5. Confirmar nombre y telefono si el flujo lo solicita.
6. Crear cita.

### Validaciones

- La cita aparece en `/admin/appointments`.
- La cita aparece en Recepcion si corresponde al dia actual.
- El paciente no se duplica si ya existia.
- La cita queda asociada al lead/comentario social cuando aplica.
- Google Calendar crea o actualiza el evento si esta conectado.
- No se mezclan estados de Pipeline con estados clinicos.

## Fase 3: Recordatorios WhatsApp

### Pasos

1. Crear cita `scheduled` o `pending_confirmation`.
2. Asegurar que el paciente tenga telefono.
3. Ejecutar el comando.

```bash
docker compose exec -T dental.app php artisan appointments:send-reminders
```

### Validaciones

- Se crea registro en `appointment_reminders`.
- Llega WhatsApp al paciente.
- No se envian primer y segundo recordatorio juntos en la misma corrida.
- Si WhatsApp falla, queda `status = failed` con `last_error`.
- Si WhatsApp envia, queda `status = sent` con `sent_at`.

## Fase 4: Respuesta CONFIRMO

### Pasos

1. Responder desde el WhatsApp del paciente:

```text
CONFIRMO
```

### Validaciones

- La cita pasa de `scheduled` o `pending_confirmation` a `confirmed`.
- Se crea evento en `appointment_events`.
- La respuesta no cae al agente comercial.
- Google Calendar se crea o actualiza.
- El evento de Google Calendar muestra `Estado: Confirmada`.
- No se envia otro recordatorio inmediato.

## Fase 5: Reprogramacion Por WhatsApp

### Pasos

Responder al recordatorio:

```text
Necesito reprogramar
```

### Validaciones

- La cita no cambia automaticamente de fecha.
- Se crea nota operativa `note_type = whatsapp`.
- Recepcion ve alerta de revision WhatsApp.
- El paciente recibe respuesta indicando que recepcion revisara el cambio.

## Fase 6: No Respuesta A Recordatorio

### Pasos

1. Configurar `Minutos sin respuesta = 1` para QA.
2. Enviar recordatorio.
3. Esperar mas de 1 minuto.
4. Ejecutar nuevamente:

```bash
docker compose exec -T dental.app php artisan appointments:send-reminders
```

### Validaciones

- Se crea nota `whatsapp_no_response`.
- Recepcion muestra alerta: paciente no respondio recordatorio WhatsApp.
- La alerta no se duplica al ejecutar nuevamente el comando.

## Fase 7: Check-In Publico

Ruta:

```text
/check-in/{clinicSlug}
```

### Casos

| Caso | Esperado |
|---|---|
| Telefono con una cita hoy | Check-in exitoso |
| Telefono con varias citas hoy | Solicita seleccionar cita |
| Codigo de cita valido | Check-in exitoso |
| Telefono no encontrado | Muestra mensaje de no encontrado |
| Cita cancelada/completada/no-show | Muestra cita no disponible |
| Cita ya registrada | Muestra llegada ya confirmada |

### Validaciones

- Check-in exitoso cambia a `checked_in`.
- `check_in_source = qr`.
- Se crea evento en `appointment_events`.
- Fallos quedan en `appointment_check_in_attempts`.
- No se guarda telefono/codigo completo en claro, solo hash y ultimos digitos.

## Fase 8: Check-In Por WhatsApp

Mensajes validos:

```text
Llegue
Ya llegue
Estoy aqui
Estoy en recepcion
Estoy en la clinica
```

### Casos

| Caso | Esperado |
|---|---|
| Una cita hoy | Check-in automatico |
| Varias citas hoy | Responde con opciones numeradas |
| Respuesta `1` o `2` | Check-in de cita seleccionada |
| Respuesta invalida | Pide responder con numero |
| Sin cita hoy | Indica acercarse a recepcion |
| Cita no disponible | No hace check-in |

### Validaciones

- `check_in_source = whatsapp`.
- No se crea lead nuevo por mensaje de llegada.
- Recepcion ve la cita en `En espera`.
- Solo cambia la cita seleccionada.

## Fase 9: Recepcion

### Probar

- Columnas: Por llegar, En espera, En preparacion, Listo para doctor, En consulta.
- Drawer de cita.
- Check-in manual.
- Agregar nota.
- Alertas de retraso.
- Alertas de espera warning/critica.
- Alertas WhatsApp de reprogramacion.
- Alertas WhatsApp de no respuesta.

### Validaciones

- Acciones usan `AppointmentFlowService`.
- Timestamps se guardan correctamente.
- Colores usan umbrales de Configuracion CRM.
- No aparecen acciones invalidas para el estado actual.

## Fase 10: Cola Clinica

### Probar Como Asistente

- Ver pacientes de doctores asignados.
- Pasar `checked_in` a `preparing`.
- Pasar `preparing` a `ready_for_doctor`.
- Agregar nota operativa.
- Revisar notas en drawer.
- Revisar colores de espera.

### Validaciones

- No ve pacientes de doctores no asignados.
- No puede operar estados fuera del flujo permitido.
- La vista funciona en mobile y desktop.

## Fase 11: Mi Cola De Atencion

### Probar Como Doctor

- Ver siguiente paciente accionable.
- Cambiar filtros: Todos, Listos, Preparando, En espera, En consulta.
- Iniciar consulta.
- Finalizar consulta.
- Agregar nota.
- Revisar colores de espera.

### Validaciones

- Prioriza `ready_for_doctor`.
- No muestra kanban completo.
- No muestra card de apoyo.
- Al finalizar consulta queda `completed`.
- Metricas se actualizan.

## Fase 12: Operacion Clinica

### Probar Como Admin

- Mini cards de volumen e incidencias.
- Espera promedio.
- Consulta promedio.
- Puntualidad.
- Estado actual de clinica.
- Alertas operativas.
- Productividad por doctor.

### Validaciones

- No permite mover pacientes.
- No tiene acciones `Umbrales` ni `Exportar` en MVP.
- Metricas salen de timestamps y eventos.

## Fase 13: Google Calendar

### Casos

| Accion | Esperado |
|---|---|
| Crear cita | Crea evento si integracion esta activa |
| Confirmar por WhatsApp | Crea/actualiza evento |
| Reprogramar | Actualiza fecha/hora |
| Cancelar | Elimina evento si aplica |
| Error de API | Guarda `external_status = sync_error` y `sync_error` |

### Validaciones

- `external_appointment_id` queda guardado.
- `external_calendar_id` queda guardado.
- `last_synced_at` se actualiza.
- La descripcion refleja paciente, doctor, procedimiento y estado.

## Fase 14: Permisos

| Pantalla | Admin | Super Admin | Recepcion | Asistente | Doctor |
|---|---|---|---|---|---|
| Recepcion | Si | Si | Si | No | No |
| Cola clinica | Si | Si | No | Si | No |
| Mi cola | Si | Si | No | No | Si |
| Operacion clinica | Si | Si | No | No | No |
| Configuracion CRM | Segun permiso | Si | No | No | No |

### Validaciones

- Super Admin tiene fallback automatico.
- Roles no ven pantallas incorrectas.
- Las transiciones siguen protegidas por `AppointmentFlowService`.

## Fase 15: Mobile/Desktop

### Probar

- Pipeline: carrusel horizontal con botones visibles en movil.
- Recepcion: columnas y drawer.
- Cola clinica: columnas y drawer.
- Mi cola: card principal y lista lateral.
- Check-in publico: formulario, seleccion multiple y mensajes.

### Validaciones

- No hay botones fuera de pantalla.
- No hay drawer cortado.
- No hay columnas imposibles de desplazar.
- No hay scroll horizontal roto.

## Comandos Utiles

Ejecutar recordatorios:

```bash
docker compose exec -T dental.app php artisan appointments:send-reminders
```

Pruebas focalizadas:

```bash
docker compose exec -T dental.app php artisan test tests/Feature/Services/AppointmentReminderServiceTest.php
docker compose exec -T dental.app php artisan test tests/Feature/Services/AppointmentReminderResponseServiceTest.php
docker compose exec -T dental.app php artisan test tests/Feature/Services/AppointmentWhatsappCheckInServiceTest.php
docker compose exec -T dental.app php artisan test tests/Feature/Http/PublicCheckInControllerTest.php
docker compose exec -T dental.app php artisan test tests/Feature/Filament/ReceptionTest.php
```

Nota: ejecutar estas suites secuencialmente, no en paralelo, porque usan `RefreshDatabase` contra la misma base de datos de Docker.

## Criterios De Cierre MVP

El MVP puede considerarse cerrado cuando:

- Pipeline crea citas sin duplicar pacientes ni citas.
- WhatsApp envia recordatorio real.
- `CONFIRMO` confirma la cita y actualiza Google Calendar.
- `REPROGRAMAR` crea nota operativa para recepcion.
- No respuesta genera alerta interna.
- `Llegue` por WhatsApp hace check-in.
- Check-in publico registra fallos.
- Recepcion mueve pacientes a espera.
- Asistente prepara y marca listo.
- Doctor inicia y finaliza consulta.
- Operacion clinica refleja metricas reales.
- Permisos por rol funcionan.
- Mobile y desktop son usables.

## Checklist Final

- [ ] Configuracion CRM validada.
- [ ] Pipeline a cita validado.
- [ ] Recordatorios WhatsApp validados.
- [ ] Confirmacion WhatsApp validada.
- [ ] Reprogramacion WhatsApp validada.
- [ ] Alerta no respuesta validada.
- [ ] Check-in publico validado.
- [ ] Check-in WhatsApp validado.
- [ ] Recepcion validada.
- [ ] Cola clinica validada.
- [ ] Mi cola doctor validada.
- [ ] Operacion clinica validada.
- [ ] Google Calendar validado.
- [ ] Permisos validados.
- [ ] Mobile/Desktop validado.
