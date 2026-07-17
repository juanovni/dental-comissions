# Fase 4 - Agenda Telefonica Con Confirmacion Estricta

## Objetivo

Permitir que Pity cree, reprograme o cancele citas solo cuando Laravel valide la accion y el paciente confirme verbalmente.

## Regla Principal

Retell puede proponer. Laravel valida. El paciente confirma. Laravel ejecuta. Retell informa.

```text
Paciente solicita cita
->
Retell recoge datos
->
Laravel consulta disponibilidad
->
Retell ofrece maximo 3 opciones
->
Paciente elige
->
Retell repite datos completos
->
Paciente confirma
->
Laravel crea cita
->
Retell confirma solo si Laravel responde success=true
```

## Datos Obligatorios Para Crear Cita

1. Nombre del paciente.
2. Telefono.
3. Procedimiento o especialidad.
4. Fecha.
5. Hora.
6. Sede.
7. Doctor cuando corresponda.

## Frase De Confirmacion

```text
Entonces confirmo tu cita para [procedimiento] el [fecha] a las [hora] en [sede], con [doctor si aplica]. Es correcto?
```

Solo si el paciente responde afirmativamente, Retell puede llamar `create_appointment`.

## Prompt Adicional Para Esta Fase

```text
Antes de crear, reprogramar o cancelar una cita, debes repetir los datos importantes y pedir confirmacion verbal.

No ejecutes la herramienta final si el paciente dice "tal vez", "creo", "dejame ver", cambia algun dato o no confirma claramente.

Si Laravel responde error o success=false, no digas que la cita quedo confirmada. Ofrece alternativas si existen o solicita transferencia a recepcion.
```

## Reglas De Disponibilidad

1. Laravel devuelve maximo 3 alternativas.
2. No ofrecer horarios fuera de lo devuelto por Laravel.
3. Si el paciente pide una fecha ambigua, pedir aclaracion.
4. Si el horario se ocupa antes de confirmar, ofrecer alternativas.
5. No sobreagendar por decision de IA.

## Cancelacion Y Reprogramacion

Recomendacion para MVP:

1. Permitir reprogramar solo citas futuras.
2. Permitir cancelar solo si reglas de negocio lo autorizan.
3. Si hay duda de identidad, transferir.
4. Registrar motivo cuando el paciente lo indique.

## Tests Criticos

1. Crea cita con confirmacion clara.
2. No crea cita sin confirmacion.
3. No crea cita si el slot ya no esta disponible.
4. Ofrece tres alternativas cuando no hay disponibilidad.
5. Reprograma preservando historial.
6. Cancela respetando reglas.
7. No confirma cuando Laravel falla.

## Criterio De Listo

Se pueden completar 20 llamadas de prueba de agenda sin citas duplicadas, sin horarios inventados y con auditoria completa.
