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

## Recomendacion Dura

No conectar el numero principal de la clinica en la primera prueba. Usar un numero piloto o desviar solo llamadas fuera de horario. Si el agente falla en el numero principal, el costo no es tecnico: es perdida de confianza del paciente.

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

## Criterio De Listo

La fase termina cuando hay un alcance que se puede probar con 30 a 50 llamadas simuladas sin ambiguedades operativas.
