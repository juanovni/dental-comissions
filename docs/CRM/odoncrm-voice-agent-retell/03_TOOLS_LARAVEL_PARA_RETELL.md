# Fase 2 - Tools Laravel Para Retell

## Objetivo

Exponer endpoints seguros que Retell pueda llamar durante la conversacion. Los endpoints deben envolver servicios internos del CRM y reutilizar la misma base de agenda que WhatsApp.

## Endpoints Recomendados

```text
POST /api/retell/calls/start
POST /api/retell/patients/search
POST /api/retell/patients/create
POST /api/retell/appointments/availability
POST /api/retell/appointments/create
POST /api/retell/appointments/reschedule
POST /api/retell/appointments/cancel
POST /api/retell/complaints/register
POST /api/retell/handoff/request
POST /api/retell/calls/finish
```

## Tools En Retell

```text
start_call
search_patient
create_patient
get_available_slots
create_appointment
reschedule_appointment
cancel_appointment
register_complaint
request_human_handoff
finish_call
```

## Contrato De Respuesta

Respuesta exitosa:

```json
{
  "success": true,
  "message": "Cita creada correctamente.",
  "data": {
    "appointment_id": 123,
    "date": "2026-07-20",
    "time": "10:30",
    "branch": "Sede Central",
    "doctor": "Dra. Ana Perez"
  }
}
```

Respuesta fallida recuperable:

```json
{
  "success": false,
  "code": "slot_unavailable",
  "message": "Ese horario ya no esta disponible.",
  "recoverable": true,
  "alternatives": [
    {
      "slot_id": "slot_1",
      "date": "2026-07-20",
      "time": "11:00",
      "branch": "Sede Central",
      "doctor": "Dra. Ana Perez"
    }
  ]
}
```

Respuesta fallida no recuperable:

```json
{
  "success": false,
  "code": "crm_error",
  "message": "No pude completar la gestion en el sistema.",
  "recoverable": false,
  "handoff_required": true
}
```

## Reglas De Implementacion

1. No devolver datos clinicos sensibles innecesarios a Retell.
2. No devolver listas largas; maximo 3 alternativas de horario.
3. No aceptar fechas ambiguas sin normalizacion previa.
4. Validar el telefono normalizado en formato E.164 cuando sea posible.
5. Registrar cada request y response en `voice_call_actions`.
6. Usar servicios compartidos con WhatsApp y Filament.

## Mapeo Con Servicios

```text
search_patient -> PatientResolutionService
get_available_slots -> AppointmentAvailabilityService
create_appointment -> AppointmentCreationService
reschedule_appointment -> AppointmentRescheduleService
cancel_appointment -> AppointmentCancellationService
finish_call -> VoiceCallAuditService
```

## Criterio De Listo

Todas las tools se pueden probar con HTTP sin Retell, usando payloads fake y verificando auditoria.
