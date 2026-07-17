# Fase 8 - Produccion Gradual Y Outbound Limitado

## Objetivo

Pasar de piloto a produccion sin disparar costos ni aumentar riesgo operativo.

## Produccion Gradual

Orden recomendado:

1. Fuera de horario.
2. Llamadas perdidas.
3. Numero piloto publicado en campanas especificas.
4. Numero principal solo si metricas son estables.
5. Llamadas salientes limitadas.

## Outbound Permitido Al Inicio

Solo casos con alto valor y baja complejidad:

1. Confirmar cita de manana.
2. Recordar cita.
3. Recuperar lead que pidio cita y no respondio.
4. Seguimiento post-consulta simple aprobado por la clinica.

## Outbound No Permitido Al Inicio

1. Ventas frias masivas.
2. Campanas sin consentimiento.
3. Cobranza.
4. Casos clinicos complejos.
5. Reclamos.

## Reglas De Costo Para Outbound

1. Lista diaria limitada.
2. Maximo de intentos por paciente.
3. No llamar si WhatsApp resolvio.
4. No llamar fuera de horario permitido.
5. Medir conversion incremental, no solo llamadas realizadas.

## Integracion Con Laravel

Crear cola de llamadas salientes:

```text
voice_call_campaigns
voice_call_campaign_items
```

Pero no implementarla antes de validar inbound.

## Criterio De Listo

Outbound solo se habilita cuando inbound ya demostro calidad, seguridad y costo por cita aceptable.
