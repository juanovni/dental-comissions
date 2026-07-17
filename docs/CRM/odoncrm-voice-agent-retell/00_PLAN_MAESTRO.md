# OdonCRM Voice Agent Retell - Plan Maestro

## Objetivo

Implementar un agente telefonico con Retell AI para atender llamadas de pacientes, registrar resultados y gestionar citas usando las mismas reglas de negocio del CRM Laravel.

El canal telefonico no debe crear una agenda paralela ni una logica propia. Retell conversa; Laravel decide, valida, registra y audita.

## Decision Tecnica

Arquitectura objetivo:

```text
Paciente llama
->
Retell AI Voice Agent
->
Tools HTTP firmadas hacia Laravel
->
Servicios comunes del CRM
->
Appointments, Patients, Professionals, Procedures, WhatsApp y auditoria
```

Twilio queda como opcion de telefonia cuando exista una razon concreta:

1. Usar o portar un numero existente.
2. Enrutamiento avanzado a recepcion.
3. Grabaciones, compliance o reglas de llamadas que Retell no cubra bien.
4. Unificar telefonia con infraestructura ya contratada.

Para minimizar costos, el piloto debe iniciar con la menor cantidad de piezas posible. Si Retell permite operar el numero piloto directamente con calidad suficiente, no se debe agregar Twilio desde el dia uno.

## Principios No Negociables

1. Laravel es la fuente de verdad para pacientes, citas, horarios, sedes, doctores y procedimientos.
2. Retell nunca escribe directo en base de datos.
3. Toda accion sensible usa tools HTTP autenticadas, idempotentes y auditables.
4. No se confirma una cita si Laravel no devuelve exito.
5. No se diagnostica por telefono.
6. Urgencias, reclamos, confusiones y errores de CRM escalan a humano.
7. Las funciones de voz reutilizan la base del flujo de WhatsApp y agenda, no duplican reglas.
8. El MVP debe medir valor antes de automatizar llamadas salientes o flujos complejos.

## Relacion Con WhatsApp

Las funciones base son las mismas que el flujo de WhatsApp para agendar, pero adaptadas al canal voz.

```text
WhatsApp Sales Agent -> texto, asincrono, tolera pausas
Retell Voice Agent -> voz, tiempo real, requiere respuestas cortas
Ambos -> servicios Laravel comunes
```

Servicios compartidos recomendados:

```text
PatientResolutionService
AppointmentAvailabilityService
AppointmentCreationService
AppointmentRescheduleService
AppointmentCancellationService
ConversationAuditService
WhatsappNotificationService
HumanHandoffService
```

## Fases

1. Fase 0: Descubrimiento, costos y limites operativos.
2. Fase 1: Modelo de llamadas, auditoria y seguridad.
3. Fase 2: Tools Laravel para Retell.
4. Fase 3: Agente Retell minimo sin creacion de citas.
5. Fase 4: Agenda telefonica con confirmacion estricta.
6. Fase 5: WhatsApp post-llamada y continuidad omnicanal.
7. Fase 6: Transferencia a humano y manejo de excepciones.
8. Fase 7: Piloto controlado, metricas y optimizacion de costos.
9. Fase 8: Produccion gradual y llamadas salientes limitadas.

## Costos A Controlar

Costos variables principales:

1. Minutos de Retell.
2. Telefonia si se usa Twilio u otro carrier.
3. Mensajes WhatsApp.
4. Tokens/modelo IA si el plan los cobra aparte.
5. Tiempo humano revisando errores.

Medidas de control:

1. Horario piloto limitado.
2. Maximo de duracion por llamada en Retell.
3. Transferir temprano cuando no haya intencion clara.
4. No usar llamadas salientes hasta demostrar ROI con entrantes.
5. Prompts breves y tools especificas para reducir vueltas conversacionales.
6. No enviar WhatsApp duplicados si la cita no cambio.
7. Guardar resumen y transcript solo con retencion definida.

## Criterio De Exito Del MVP

El MVP solo se considera exitoso si cumple esto durante el piloto:

1. Crea o gestiona citas sin duplicarlas.
2. No confirma horarios inexistentes.
3. Escala urgencias y reclamos correctamente.
4. Reduce carga de recepcion o captura llamadas perdidas.
5. El costo por cita gestionada es menor que el beneficio operativo esperado.
6. El equipo puede auditar cada llamada desde el CRM.

Si no se cumple el punto 5, no se debe escalar automatizacion. Se debe ajustar alcance o volver a recepcion asistida.
