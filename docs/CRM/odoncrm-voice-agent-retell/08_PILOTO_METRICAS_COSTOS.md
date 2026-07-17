# Fase 7 - Piloto Controlado, Metricas Y Costos

## Objetivo

Probar con llamadas reales de bajo riesgo y decidir con datos si conviene escalar.

## Configuracion Recomendada Del Piloto

1. Numero piloto o desvio fuera de horario.
2. Duracion de 1 a 2 semanas.
3. Horario limitado.
4. Sin llamadas salientes masivas.
5. Revision diaria de llamadas fallidas.
6. Recepcion informada y preparada para transferencias.

## Metricas De Calidad

1. Llamadas totales.
2. Llamadas resueltas sin humano.
3. Citas creadas.
4. Citas reprogramadas.
5. Citas canceladas.
6. Transferencias.
7. Urgencias detectadas.
8. Reclamos detectados.
9. Errores de CRM.
10. Citas corregidas manualmente.

## Metricas De Costo

```text
costo_total_retell
costo_total_telefonia
costo_total_whatsapp
costo_total_estimado
costo_por_llamada
costo_por_cita_confirmada
minutos_promedio_por_llamada
```

## Umbrales De Decision

Escalar solo si:

1. Menos del 5% de llamadas requiere correccion manual por error del agente.
2. 0 citas inventadas o confirmadas sin disponibilidad.
3. 100% de urgencias simuladas fueron escaladas.
4. Costo por cita confirmada es aceptable para la clinica.
5. Recepcion percibe reduccion de carga, no aumento.

Si no se cumplen estos puntos, no escalar. Ajustar prompt, tools o alcance.

## Optimizacion De Costos

1. Reducir saludo inicial.
2. Evitar preguntas dobles.
3. Transferir antes en casos fuera de alcance.
4. Limitar alternativas de agenda a 3.
5. Evitar mensajes WhatsApp no operativos.
6. No guardar audio indefinidamente.
7. No activar outbound hasta medir conversion inbound.

## Criterio De Listo

Hay un reporte de piloto con calidad, costos y recomendacion clara: escalar, ajustar o detener.
