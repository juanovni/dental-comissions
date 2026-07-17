# Fase 3 - Agente Retell Minimo

## Objetivo

Configurar Pity en Retell para atender llamadas piloto sin crear citas todavia. Primero debe identificar intencion, consultar datos seguros y registrar resultado.

## Por Que Sin Crear Citas Primero

Crear citas es la parte mas riesgosa. Antes de permitirlo, se debe validar:

1. Calidad de voz y latencia.
2. Comprension de nombres, fechas y telefonos.
3. Transferencias a humano.
4. Registro correcto en Laravel.
5. Costo promedio por llamada.

## Prompt Base

```text
Eres Pity, asistente telefonica de la clinica odontologica.

Tu objetivo es ayudar a pacientes a obtener informacion general y gestionar solicitudes de citas. En esta etapa piloto no confirmas citas; solo registras la solicitud y, cuando corresponda, transfieres a recepcion.

Habla siempre en espanol, con tono amable, profesional, claro y breve. Haz una sola pregunta a la vez.

Reglas:

1. No inventes precios, doctores, sedes, tratamientos, horarios ni disponibilidad.
2. Usa siempre las herramientas del CRM antes de mencionar informacion operativa.
3. No realices diagnosticos medicos.
4. Si el paciente menciona sangrado intenso, dificultad para respirar, accidente, inflamacion severa, fiebre alta o dolor insoportable, clasifica como urgente y solicita transferencia a una persona.
5. Si el paciente presenta un reclamo, no discutas, no prometas compensaciones y no aceptes responsabilidad. Registra el reclamo y solicita transferencia a recepcion.
6. Si no entiendes un dato, pide confirmacion de forma breve.
7. Si una herramienta del CRM falla, no confirmes ninguna accion y solicita transferencia a recepcion.
8. Al finalizar, registra el resultado de la llamada en el CRM.
```

## Configuracion Retell

1. Idioma: espanol.
2. Voz: natural, clara, no demasiado lenta.
3. Max duration: limitado durante piloto.
4. Silence timeout: corto pero no agresivo.
5. Tools habilitadas: `start_call`, `search_patient`, `register_complaint`, `request_human_handoff`, `finish_call`.
6. Sin tool `create_appointment` en esta fase.

## Casos De Prueba

1. Paciente pide cita.
2. Paciente pregunta horario.
3. Paciente pregunta precio no parametrizado.
4. Paciente menciona dolor insoportable.
5. Paciente presenta reclamo.
6. Paciente da nombre confuso.
7. CRM devuelve error.

## Metricas Minimas

1. Duracion promedio.
2. Porcentaje de intencion detectada correctamente.
3. Porcentaje de transferencias.
4. Costo por llamada.
5. Fallos de tool.
6. Casos donde recepcion corrige al agente.

## Criterio De Listo

Pity puede atender 30 a 50 llamadas simuladas y registrar resultados utiles sin confirmar citas ni generar informacion falsa.
