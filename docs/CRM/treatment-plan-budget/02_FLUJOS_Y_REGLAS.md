# Flujos y Reglas - Plan de Tratamiento y Presupuesto

## Flujo Principal

```mermaid
flowchart TD
    A[Consulta clinica] --> B[Diagnostico]
    B --> C[Crear plan]
    C --> D[Agregar procedimientos]
    D --> E[Calcular y revisar revision]
    E --> F[Aprobar]
    F --> G[Enviar enlace al paciente]
    G --> H[Registrar visualizacion]
    H --> I{Decision del paciente}
    I -->|Acepta todo| J[Items comercialmente aceptados]
    I -->|Acepta parcialmente| K[Separar aceptados y no aceptados]
    I -->|Solicita contacto| L[Crear seguimiento]
    I -->|Rechaza| M[Registrar motivo]
    J --> N[Evaluar preparacion clinica]
    K --> N
    K --> L
    N --> O[Agendamiento y atencion]
    O --> P[Crear encuentro clinico]
    P --> Q[Confirmar procedimientos realizados]
    Q --> R[Actualizar caso y avance]
```

## Creacion Y Emision

1. El profesional crea el plan desde la ficha del paciente o una cita finalizada.
2. Registra diagnostico general y prioridad clinica.
3. Agrega procedimientos desde el catalogo.
4. OdonCRM copia nombre, codigo, precio, duracion y sesiones como snapshot.
5. El profesional completa pieza, superficie, prioridad y observaciones.
6. Un usuario autorizado revisa precios y descuentos.
7. El sistema calcula los totales en servidor.
8. Se aprueba una revision inmutable.
9. Se genera un enlace publico seguro.
10. El envio se registra con canal, destinatario y actor.

Reglas:

- Un doctor no modifica precios si no tiene permiso comercial.
- Un cambio posterior al envio crea una nueva revision.
- La revision anterior conserva su historial y respuesta.
- No se puede enviar un plan sin items vigentes.
- No se puede emitir un plan con precios o duraciones invalidas.

## Experiencia Del Paciente

El enlace debe mostrar:

- Identificacion minima del paciente y clinica.
- Titulo y vigencia del plan.
- Procedimientos, piezas y explicacion autorizada.
- Precio por item y total.
- Condiciones comerciales.
- Acciones para aceptar, rechazar, solicitar contacto o agendar.

El enlace no debe mostrar:

- Notas internas.
- Scores comerciales.
- Motivos internos de prioridad.
- Historial de otros pacientes.
- Datos clinicos no necesarios para la decision.

Acciones:

1. Aceptar todos los items vigentes.
2. Seleccionar items y aceptar parcialmente.
3. Rechazar items seleccionados.
4. Solicitar una llamada o mensaje.
5. Solicitar agendamiento para items aceptados.
6. Descargar la revision presentada.

Cada accion crea un evento y conserva la revision exacta sobre la cual se respondio.

## Conversion A Citas

```mermaid
flowchart TD
    A[Item aceptado] --> B[Evaluar preparacion clinica]
    B --> C{Cumple requisitos}
    C -->|No| D[Registrar bloqueo o accion requerida]
    C -->|Si| F{Tiene cita activa que cubre el item}
    F -->|Si| G[Mostrar cita relacionada]
    F -->|No| E[Crear o reutilizar cita planificada]
    E --> H[Buscar disponibilidad]
    H --> I[Seleccionar profesional y horario]
    I --> J[Revalidar disponibilidad y preparacion]
    J -->|Disponible| K[Reservar atomicamente]
    J -->|Ocupado o bloqueado| L[Ofrecer alternativas o resolver bloqueo]
    K --> M[Relacionar cita e items]
    M --> N[Sincronizar Google Calendar]
    N --> O[Notificar al paciente]
```

Reglas:

- El paciente puede tener varias citas futuras.
- La duracion se calcula desde los items seleccionados y reglas configuradas.
- Todas las rutas de creacion y reprogramacion usan el mismo servicio de reserva.
- La reserva debe impedir doble booking a nivel transaccional.
- Un fallo de Google Calendar no debe perder la reserva interna.
- La sincronizacion externa debe tener reintentos y estado visible.
- La preparacion clinica valida historia revisada, diagnostico, secuencia, autorizacion, consentimiento, competencia profesional y bloqueos.
- El sistema revalida la preparacion antes de registrar el procedimiento realizado.

## Aceptacion Y Consentimiento

Se deben registrar por separado:

- Aceptacion comercial de precios y condiciones.
- Autorizacion del paciente o representante para proceder.
- Consentimiento informado especifico cuando aplique.
- Consentimiento para comunicaciones, fotografias o intercambio de datos.

El consentimiento informado debe conservar plantilla, version, idioma, procedimiento, pieza o sitio, riesgos, alternativas, firmante, capacidad o representacion, profesional, fecha y evidencia. Puede retirarse o expirar sin borrar versiones anteriores.

## Preparacion Clinica

Un item puede estar aceptado y seguir no listo. La evaluacion incluye:

1. Antecedentes revisados y vigentes.
2. Alergias, medicamentos y alertas reconocidas.
3. Diagnostico confirmado y evidencia revisada.
4. Fase y dependencias satisfechas.
5. Resultados, referidos o autorizaciones requeridas.
6. Consentimiento aplicable vigente.
7. Profesional competente y disponible.
8. Tiempo de cicatrizacion o intervalo cumplido.
9. Ausencia de bloqueo clinico activo.

Las excepciones requieren usuario autorizado, motivo y evento auditable.

### Evaluacion De Antecedentes Y Bloqueos

1. Utilizar la ultima version revisada del perfil medico e identificar cambios posteriores.
2. Evaluar alertas y reglas aplicables al procedimiento, tecnica, medicamentos, alergias y condiciones.
3. Registrar el resultado y las versiones de evidencia utilizadas.
4. Ejecutar una evaluacion para agendamiento y otra inmediatamente antes de la atencion.
5. Permitir override solo para `conditional_hold`, por un profesional autorizado, con motivo, alcance y vencimiento.
6. Resolver un `hard_stop` mediante nueva evidencia o una decision clinica prevista, nunca mediante confirmacion de recepcion.

El sistema debe priorizar pocas alertas accionables y revisadas sobre advertencias masivas. No debe afirmar que cubre todas las interacciones farmacologicas si no utiliza una fuente clinica validada y mantenida.

### Interconsulta Y Autorizacion Medica Externa

```text
Requisito identificado
-> solicitud enviada
-> respuesta recibida
-> revision profesional de alcance y vigencia
-> decision registrada
-> recalculo de preparacion
```

Recepcion puede dar seguimiento a la solicitud, pero no interpretar ni aprobar la respuesta. `cleared_with_conditions` crea condiciones verificables; un cambio relevante del perfil medico o el vencimiento reabre el requisito. Los documentos permanecen en storage clinico privado.

### Preparacion Dependiente De Laboratorio Y Dispositivos

- Una cita de prueba o entrega puede reservarse tentativamente, pero no ejecutarse hasta que el trabajo este recibido y aprobado para el paciente.
- Un ajuste o remake reabre el bloqueo sin borrar entregas anteriores.
- Los dispositivos criticos se validan por disponibilidad, vencimiento y recall antes de ejecutar.
- El lote o serial realmente usado se registra antes de completar el procedimiento o firmar el encuentro.

## Flujo Abreviado De Urgencia O Atencion Sin Cita

La urgencia no requiere un plan ni presupuesto previo, pero conserva un conjunto minimo de seguridad:

1. Identificar al paciente o crear identidad provisional para conciliacion posterior.
2. Registrar forma de llegada, motivo, intensidad, evolucion y senales de alarma.
3. Realizar tamizaje agudo de alergias, medicamentos, condiciones y cambios recientes.
4. Revisar alertas y bloqueos activos.
5. Registrar capacidad de decision o representante.
6. Obtener el consentimiento aplicable a la intervencion urgente.
7. Crear encuentro clinico aunque no exista cita o item del plan.
8. Registrar examen, diagnostico provisional o definitivo, intervencion, medicamentos, resultado y disposicion.
9. Crear seguimiento, referido o plan posterior para necesidades no resueltas.
10. Completar y conciliar informacion diferida dentro del plazo configurado.

El acceso `break_glass` amplia temporalmente permisos en una emergencia, pero no elimina bloqueos clinicos ni requisitos de documentacion. Debe registrar usuario, motivo, alcance, registros consultados y revision posterior.

## Confirmacion De Realizacion

Finalizar la cita abre una confirmacion clinica:

1. Crear o abrir el encuentro clinico asociado.
2. Mostrar items planificados para la cita.
3. Marcar cuales se realizaron.
4. Registrar cantidad o sesion completada.
5. Registrar desviaciones o procedimientos no realizados.
6. Actualizar el caso especializado cuando exista.
7. Actualizar el estado clinico de cada item.
8. Recalcular avance del plan.
9. Crear eventos de ejecucion.

La cita `completed` y el item `completed` son hechos relacionados, pero distintos.

## Apertura De Caso Clinico

```mermaid
flowchart TD
    A[Item del plan] --> B{Requiere seguimiento especializado}
    B -->|No| C[Agendar y registrar procedimiento realizado]
    B -->|Si| D[Crear o vincular caso clinico]
    D --> E{Tipo de caso}
    E -->|Ortodoncia| F[Plan longitudinal y controles]
    E -->|Endodoncia| G[Caso por pieza y conductos]
    E -->|Periodoncia| H[Examen y mediciones periodontales]
    F --> I[Encuentros sucesivos]
    G --> I
    H --> I
    I --> J[Procedimientos realizados]
    J --> K[Actualizar avance del item y caso]
```

Reglas:

- Limpieza, resina simple o blanqueamiento no crean automaticamente un expediente especializado.
- Una endodoncia normalmente crea un caso por pieza tratada.
- Ortodoncia crea un caso longitudinal; los controles pertenecen al mismo caso.
- Periodoncia puede crear un caso con examenes y reevaluaciones sucesivas.
- El caso puede comenzar durante el diagnostico y vincularse al item despues.
- El presupuesto nunca almacena hallazgos clinicos especializados.

## Aplicacion De Secuencia Clinica

1. Una version publicada de reglas genera recomendaciones al construir el borrador.
2. El profesional revisa fases, dependencias, intervalos y requisitos antes de aprobar el plan.
3. Las reglas aceptadas se materializan como dependencias propias del plan individual.
4. Una nueva version solo afecta borradores nuevos o recalculados expresamente.
5. Una excepcion registra regla original, decision, motivo, profesional y vigencia.
6. Cambiar o retirar una dependencia recalcula preparacion y consentimientos aplicables.
7. Agenda y recuperacion consultan el plan individual, no la ultima regla global.

## Seguimiento Comercial

La cola comercial incluye:

- Plan enviado y no visualizado.
- Plan visualizado sin respuesta.
- Item propuesto sin decision.
- Plan proximo a vencer.
- Rechazo temporal recuperable.
- Solicitud de contacto pendiente.

Acciones:

- Enviar mensaje individual.
- Registrar llamada.
- Programar proxima accion.
- Registrar resultado.
- Registrar rechazo u opt-out.
- Crear una nueva revision autorizada.

## Seguimiento Clinico Y Recall

El seguimiento clinico es independiente del contacto comercial:

- Control postoperatorio.
- Revision de sintomas o complicaciones.
- Resultado diagnostico pendiente.
- Reevaluacion periodontal.
- Control de ortodoncia o endodoncia.
- Mantenimiento preventivo.
- Recall por intervalo configurable.

Cada tarea registra motivo, responsable, fecha requerida, instrucciones, resultado, escalamiento y cierre. El recall se calcula desde procedimientos realizados confirmados, no desde citas creadas.

El recall preventivo se estratifica por riesgo:

```text
Evaluacion de riesgo vigente
+ tipo de recall
+ version de politica
+ ultimo evento clinico calificante
= fecha calculada
```

El profesional puede ajustar la fecha con motivo. Se recalcula ante cambios de riesgo, diagnostico o procedimiento relevante; se conserva el calculo anterior y su procedencia. Un paciente puede estar al dia en un tipo de recall y vencido en otro. El opt-out detiene comunicaciones, pero no elimina la necesidad clinica ni su fecha.

## Recuperacion De Agenda

La recuperacion de agenda solo ofrece directamente procedimientos que cumplan las reglas de elegibilidad.

Elegibilidad minima:

1. Item comercialmente aceptado.
2. Item clinicamente vigente.
3. Sin cita activa que lo cubra.
4. Profesional y especialidad compatibles.
5. Duracion compatible con el espacio.
6. Paciente habilitado para contacto.
7. Frecuencia y horario de contacto permitidos.
8. Sin contraindicion o bloqueo registrado.

```mermaid
flowchart TD
    A[Cancelacion o espacio disponible] --> B[Crear brecha]
    B --> C[Definir profesional y duracion]
    C --> D[Buscar items aceptados sin cita]
    D --> E[Filtrar elegibilidad]
    E --> F[Calcular puntaje explicable]
    F --> G[Mostrar candidatos]
    G --> H[Equipo selecciona paciente]
    H --> I[Enviar oferta]
    I --> J{Respuesta}
    J -->|Acepta| K[Reservar atomicamente]
    J -->|Rechaza| L[Registrar resultado]
    J -->|Sin respuesta| M[Ofrecer siguiente candidato]
```

## Puntaje De Recuperacion

Factores obligatorios:

- Compatibilidad de profesional.
- Compatibilidad de especialidad.
- Vigencia y aceptacion del item.
- Disponibilidad real.
- Reglas de contacto.

Factores ponderados:

- Ajuste de duracion.
- Prioridad clinica.
- Antiguedad sin agendar.
- Preferencia horaria.
- Continuidad de tratamiento.
- Tiempo desde ultimo contacto.
- Historial de respuesta.
- Riesgo de no-show.
- Valor pendiente.

Cada recomendacion debe explicar sus principales razones. El monto nunca puede desplazar una prioridad clinica superior.

## Ejemplo De Recomendacion

```text
Paciente: Maria Torres
Procedimiento: Resina en pieza 11
Duracion: 45 minutos
Horario sugerido: manana a las 10:00
Compatibilidad: 96%

Razones:
- Procedimiento aceptado.
- Sin cita activa.
- Duracion exacta para el espacio.
- Profesional compatible.
- Prioridad clinica media-alta.
- Frecuencia de contacto permitida.
```

## WhatsApp Y Comunicaciones

Reglas:

1. El primer envio individual registra destinatario, plantilla y revision.
2. Los mensajes nunca inventan precios, descuentos o diagnosticos.
3. Fuera de la ventana permitida se utilizan plantillas aprobadas.
4. Se respetan consentimiento, opt-out, horarios y frecuencia maxima.
5. Una respuesta se asocia al plan y al intento de seguimiento.
6. El rechazo definitivo detiene automatizaciones.
7. Casos clinicos sensibles escalan a una persona.
8. Los webhooks deben verificar firma e idempotencia.

## Integracion Con Pipeline Social

Cuando existe un lead de origen:

```text
Plan creado              -> conservar atribucion social
Plan enviado             -> etapa Presupuesto
Plan visualizado         -> registrar engagement
Aceptacion parcial       -> mantener Presupuesto con valor aceptado
Aceptacion total         -> aplicar regla comercial configurada
Primera cita agendada    -> registrar conversion a cita
Tratamiento iniciado     -> candidato a Ganado
Rechazo definitivo       -> Perdido con motivo
```

La definicion exacta de `Ganado` debe ser una decision de producto configurable o documentada. No debe inferirse solamente por enviar un presupuesto.

## Excepciones

- Plan vencido: no admite nueva aceptacion sin reemision.
- Revision reemplazada: redirige a la revision vigente sin ocultar historial interno.
- Item retirado: conserva historial, pero deja de ser aceptable.
- Cita cancelada: el item vuelve a `unscheduled` si sigue aceptado y vigente.
- No-show: crea seguimiento de reprogramacion segun politica.
- Profesional inactivo: requiere reasignacion antes de agendar.
- Precio modificado: requiere nueva revision y, cuando aplique, nueva aceptacion.
- Paciente con opt-out: no recibe automatizaciones; el equipo puede ver la restriccion.
