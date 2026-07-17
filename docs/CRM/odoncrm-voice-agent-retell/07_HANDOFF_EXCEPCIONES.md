# Fase 6 - Transferencia A Humano Y Excepciones

## Objetivo

Definir cuando Pity debe dejar de automatizar y transferir a recepcion o equipo clinico.

## Transferencia Obligatoria

1. Sangrado intenso.
2. Dificultad para respirar.
3. Accidente o trauma.
4. Inflamacion severa.
5. Fiebre alta.
6. Dolor insoportable.
7. Reclamo.
8. Paciente molesto o confundido.
9. CRM no disponible.
10. Identidad no confiable para cancelar o reprogramar.
11. Solicitud fuera del alcance configurado.

## Politica Operativa

Si no hay humano disponible:

1. Registrar llamada como `human_handoff_required=true`.
2. Crear alerta interna.
3. Enviar WhatsApp solo si corresponde.
4. No prometer tiempos exactos de llamada si no estan parametrizados.

## Frases Permitidas

```text
Para ayudarte de forma responsable, voy a derivarte con recepcion.
```

```text
No quiero darte una indicacion incorrecta. Nuestro equipo debe revisar este caso directamente.
```

```text
Ya registre tu solicitud para que recepcion le de seguimiento.
```

## Frases Prohibidas

```text
Eso no es grave.
```

```text
Te recomiendo tomar...
```

```text
Te aseguro que te compensaremos.
```

```text
La cita ya esta confirmada.
```

Si Laravel no respondio exito, esa ultima frase esta prohibida.

## Entregables

1. Tool `request_human_handoff`.
2. Registro de motivo de transferencia.
3. Alerta visible en Filament.
4. Tests de urgencias y reclamos.

## Criterio De Listo

Toda urgencia o reclamo termina con transferencia o alerta interna, nunca con una respuesta clinica automatizada.
