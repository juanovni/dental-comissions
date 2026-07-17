# Fase 0 - Descubrimiento, Costos Y Limites

## Objetivo

Definir alcance real antes de configurar Retell o construir integraciones. Esta fase evita gastar en minutos, telefonia y desarrollo para flujos que la clinica no necesita todavia.

## Decisiones Que Deben Cerrarse

1. Horario en que Pity atiende llamadas.
2. Si el agente atiende solo llamadas perdidas o todas las llamadas entrantes.
3. Si se usa numero piloto nuevo o numero principal.
4. Si Retell maneja telefonia directamente o se agrega Twilio.
5. Lista exacta de sedes, doctores, especialidades y procedimientos disponibles para agenda.
6. Reglas de cancelacion y reprogramacion.
7. Cuando transferir a recepcion.
8. Politica de grabacion y retencion de transcripciones.

## Decisiones Operativas Del Piloto

Estas decisiones aplican para reducir costo, riesgo clinico y errores operativos durante la primera version.

Resumen aprobado para el piloto:

```text
horario: lunes a viernes, 09:00-11:00 y 15:00-17:00
rollout_inicial: 5% de llamadas elegibles
responsable_humano: Juan Constantine titular + Joe Aspiazu suplente
telefono: numero piloto nuevo
telefonia: Retell directo, sin Twilio inicialmente
semana_1: Pity solo registra solicitudes y transfiere; no crea citas reales
whatsapp: solo enviar confirmacion cuando recepcion confirme la cita
umbral_costo_temporal: USD 3 a USD 7 por cita confirmada
```

### Horario De Funcionamiento

Pity solo debe funcionar en horarios establecidos donde exista una recepcionista disponible para recibir transferencias inmediatamente.

No iniciar con atencion nocturna, fines de semana ni feriados. Aunque parece atractivo capturar llamadas fuera de horario, el riesgo operativo es mayor: si aparece una urgencia, reclamo o falla tecnica, no habria una persona disponible para intervenir.

Decision inicial recomendada:

```text
modo_piloto: horario_controlado
requiere_recepcionista_disponible: true
horario_piloto: lunes a viernes, 09:00-11:00 y 15:00-17:00
noches: desactivado
fines_de_semana: desactivado
feriados: desactivado
```

Ventana piloto aprobada:

```text
lunes: 09:00-11:00, 15:00-17:00
martes: 09:00-11:00, 15:00-17:00
miercoles: 09:00-11:00, 15:00-17:00
jueves: 09:00-11:00, 15:00-17:00
viernes: 09:00-11:00, 15:00-17:00
sabado: desactivado
domingo: desactivado
```

### Porcentaje De Llamadas

El piloto debe activarse por porcentaje de llamadas, no para todo el trafico desde el inicio.

El porcentaje aplica solo a llamadas elegibles dentro de la ventana piloto aprobada. Las llamadas fuera de ese horario no deben entrar al agente en esta fase.

Escalamiento recomendado:

```text
semana_1: 5% de llamadas elegibles
semana_2: 10% si no hubo incidentes criticos
semana_3: 20% si calidad y costos son aceptables
semana_4: 30% a 50% solo con aprobacion operativa
```

No subir porcentaje si ocurre cualquiera de estos eventos:

1. Una cita confirmada sin disponibilidad real.
2. Una urgencia no transferida.
3. Un reclamo no transferido.
4. Mas de 5% de llamadas con correccion manual por error del agente.
5. Costo por cita confirmada por encima del umbral definido.
6. Recepcion reporta que el agente aumenta la carga en vez de reducirla.

### Llamadas Elegibles

Durante el piloto, Pity solo debe atender llamadas de bajo riesgo.

Elegibles:

1. Solicitudes de informacion general.
2. Intencion de agendar valoracion o consulta.
3. Reprogramacion simple.
4. Cancelacion simple bajo reglas del CRM.

No elegibles:

1. Urgencias clinicas.
2. Reclamos.
3. Casos medico-legales.
4. Pacientes molestos.
5. Solicitudes de cobro o financiamiento.
6. Cualquier flujo donde recepcion no pueda intervenir.

### Responsable Humano

Cada franja debe tener una recepcionista titular y una suplente. No basta con que "alguien" pueda contestar.

Configuracion aprobada:

```text
09:00-11:00: Juan Constantine como titular, Joe Aspiazu como suplente
15:00-17:00: Juan Constantine como titular, Joe Aspiazu como suplente
```

Si no hay responsable confirmado para una franja, Pity no debe recibir llamadas reales en esa franja.

### Numero Telefonico

El piloto debe usar un numero piloto nuevo. No usar el numero principal de la clinica al inicio.

Razon:

1. Reduce riesgo reputacional.
2. Permite probar Retell sin afectar operacion principal.
3. Facilita cortar el piloto si hay errores.
4. Permite medir trafico y conversion con mas claridad.

El porcentaje del numero principal solo debe considerarse despues de validar el piloto.

### Telefonia

La decision inicial es usar Retell directo, sin Twilio.

Twilio se agrega solo si aparece una necesidad concreta:

1. Portar o usar un numero existente.
2. Enrutamiento avanzado.
3. Reglas de central telefonica.
4. Grabacion o compliance que Retell no cubra.
5. Integracion con infraestructura telefonica existente.

Mientras no exista una de esas necesidades, agregar Twilio seria costo y complejidad prematura.

### Alcance Semana 1

Durante la primera semana, Pity no debe crear citas reales.

Permitido:

1. Atender la llamada.
2. Identificar intencion.
3. Recoger nombre, telefono, motivo y preferencia de horario.
4. Registrar solicitud en el CRM.
5. Transferir a recepcion cuando corresponda.
6. Registrar resumen y resultado de llamada.

No permitido:

1. Confirmar citas reales.
2. Reprogramar citas reales.
3. Cancelar citas reales.
4. Prometer horarios.

La creacion real de citas se habilita solo despues de validar calidad de llamadas, transferencia, auditoria y costo.

### WhatsApp En Semana 1

No enviar WhatsApp por cada llamada. Enviar WhatsApp solo cuando recepcion confirme una cita o cuando exista una razon operativa clara.

Esto reduce costos y evita mensajes inutiles o confusos.

### Umbral Temporal De Costo

Hasta conocer margen real por tipo de cita, usar este limite temporal:

```text
costo_maximo_aceptable_por_cita_confirmada: USD 3 a USD 7
```

Este umbral debe reemplazarse luego por una regla basada en margen:

```text
costo_maximo_por_cita = 3% a 5% del margen bruto promedio de una cita
```

### Regla De Transferencia Inmediata

La transferencia solo cuenta como valida si una persona puede responder en ese momento o si queda una alerta interna con seguimiento operativo claro.

Si no hay recepcionista disponible, Pity no debe operar en modo autonomo para llamadas reales.

## Recomendacion Dura

No conectar el numero principal de la clinica en la primera prueba. Usar un numero piloto o desviar solo llamadas fuera de horario. Si el agente falla en el numero principal, el costo no es tecnico: es perdida de confianza del paciente.

Con la decision de operar solo cuando exista recepcionista disponible, el desvio fuera de horario queda descartado para el primer piloto. El enfoque mas seguro es numero piloto o porcentaje bajo de llamadas elegibles dentro del horario operativo.

## Alcance MVP Permitido

1. Responder informacion general parametrizada.
2. Identificar paciente por telefono.
3. Registrar llamada.
4. Consultar disponibilidad.
5. Crear cita confirmada por el paciente.
6. Reprogramar o cancelar bajo reglas del CRM.
7. Enviar WhatsApp de confirmacion.
8. Transferir urgencias y reclamos.

## Fuera Del MVP

1. Diagnostico clinico.
2. Cobros por telefono.
3. Financiamiento.
4. Llamadas salientes masivas.
5. Manejo de casos medico-legales.
6. Promociones dinamicas sin aprobacion.
7. Integracion directa de Retell con base de datos.

## Checklist De Costos

Antes de avanzar, documentar:

```text
costo_retell_por_minuto
costo_telefonia_por_minuto
costo_whatsapp_confirmacion
duracion_promedio_esperada
tasa_esperada_de_citas
costo_maximo_aceptable_por_cita
```

Valores iniciales para piloto:

```text
costo_retell_por_minuto: pendiente de plan contratado
costo_telefonia_por_minuto: pendiente; inicialmente Retell directo
costo_whatsapp_confirmacion: pendiente de tarifa Meta por pais y categoria
duracion_promedio_esperada: 2 a 4 minutos
tasa_esperada_de_citas: medir en piloto, no asumir
costo_maximo_aceptable_por_cita: USD 3 a USD 7 temporal
```

Formula minima:

```text
costo_por_cita = costo_total_llamadas / citas_confirmadas
```

Si el costo por cita supera el margen operativo esperado, no escalar.

## Entregables

1. Documento de alcance firmado por negocio.
2. Tabla de costos estimados.
3. Lista de flujos permitidos y bloqueados.
4. Decision Retell directo vs Retell + Twilio.
5. Numero piloto definido.
6. Horario exacto de piloto con responsable humano asignado.
7. Porcentaje inicial de llamadas y regla de escalamiento semanal.

## Estado De Entregables

```text
alcance_operativo: definido
horario_piloto: definido
rollout_inicial: definido
telefonia_inicial: Retell directo
numero_piloto: decidido, pendiente de compra/configuracion
responsables_por_franja: Juan Constantine titular, Joe Aspiazu suplente
costos_reales: pendiente de plan Retell y tarifas Meta
```

## Pendientes Para Cerrar Fase 0

1. Comprar o asignar numero piloto nuevo.
2. Confirmar plan Retell y costo real por minuto.
3. Confirmar tarifa WhatsApp aplicable por pais y categoria de plantilla.
4. Crear tabla simple de seguimiento de costos durante piloto.

## Criterio De Listo

La fase termina cuando hay un alcance que se puede probar con 30 a 50 llamadas simuladas sin ambiguedades operativas.
