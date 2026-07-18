<?php

namespace App\Services;

use App\Models\Appointment;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Models\Procedure;
use App\Models\Professional;
use Carbon\Carbon;

class VoiceToolService
{
    public function __construct(
        private VoicePatientResolver $patientResolver,
        private VoiceAppointmentHoldService $holdService,
    ) {}

    public function identifyPatient(array $params): array
    {
        $phoneE164 = $params['phone_e164'] ?? null;

        if (blank($phoneE164)) {
            throw new \InvalidArgumentException('phone_e164 es requerido.');
        }

        $patient = $this->patientResolver->find($phoneE164);

        if (! $patient) {
            return [
                'found' => false,
                'patient_id' => null,
                'name' => null,
            ];
        }

        return [
            'found' => true,
            'patient_id' => $patient->id,
            'name' => $patient->full_name,
        ];
    }

    public function getAvailableSlots(array $params): array
    {
        $search = app(AppointmentSlotSearchService::class);

        $request = [
            'doctor_id' => $params['doctor_id'] ?? null,
            'date' => $params['preferred_date'] ?? null,
            'period' => $params['preferred_period'] ?? null,
        ];

        $procedureName = $params['procedure_name'] ?? null;

        if ($procedureName) {
            $procedure = Procedure::where('name', 'ilike', "%{$procedureName}%")
                ->orWhere('code', 'ilike', "%{$procedureName}%")
                ->first();

            if ($procedure) {
                $request['procedure_id'] = $procedure->id;
            }
        }

        $rawSlots = $search->search($request);

        return [
            'slots' => array_map(fn (array $slot): array => [
                'datetime' => $slot['datetime'],
                'label' => $slot['label'],
                'doctor_id' => $slot['doctor_id'] ?? null,
                'doctor_name' => $slot['doctor_name'] ?? null,
            ], $rawSlots),
        ];
    }

    public function holdSlot(array $params): array
    {
        $slotDatetime = $params['slot_datetime'] ?? null;
        $doctorId = $params['doctor_id'] ?? null;
        $procedureId = $params['procedure_id'] ?? null;
        $phoneE164 = $params['phone_e164'] ?? null;

        if (blank($slotDatetime) || blank($doctorId) || blank($procedureId)) {
            throw new \InvalidArgumentException('slot_datetime, doctor_id y procedure_id son requeridos.');
        }

        return $this->holdService->create(
            doctorId: (int) $doctorId,
            procedureId: (int) $procedureId,
            startsAt: $slotDatetime,
            phoneE164: $phoneE164 ?? 'unknown',
        );
    }

    public function createAppointment(array $params): array
    {
        $holdToken = $params['hold_token'] ?? null;
        $patientName = $params['patient_name'] ?? null;
        $phoneE164 = $params['phone_e164'] ?? null;
        $procedureId = $params['procedure_id'] ?? null;
        $notes = $params['notes'] ?? 'Agendado por Pity Voice';

        if (blank($holdToken) || blank($patientName) || blank($phoneE164)) {
            throw new \InvalidArgumentException('hold_token, patient_name y phone_e164 son requeridos.');
        }

        $hold = $this->holdService->consume($holdToken);

        $patient = $this->patientResolver->findOrCreate($patientName, $phoneE164);

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'procedure_id' => $procedureId ?: $hold->procedure_id,
            'doctor_id' => $hold->doctor_id,
            'scheduled_at' => $hold->starts_at,
            'duration_minutes' => $hold->starts_at?->diffInMinutes($hold->ends_at) ?: null,
            'status' => AppointmentStatus::PendingConfirmation,
            'source' => AppointmentSource::VoiceCall,
            'notes' => $notes,
        ]);

        $hold->update(['appointment_id' => $appointment->id]);

        $this->syncToWhatsApp($appointment, $patient);

        return [
            'appointment_id' => $appointment->id,
            'status' => $appointment->status->value,
            'confirmation_message' => sprintf(
                'Tu cita fue agendada para el %s a las %s.',
                $appointment->scheduled_at->isoFormat('dddd D [de] MMMM'),
                $appointment->scheduled_at->format('g:i A'),
            ),
        ];
    }

    public function requestHandoff(array $params): array
    {
        $reason = $params['reason'] ?? null;
        $summary = $params['summary'] ?? null;

        if (blank($reason)) {
            throw new \InvalidArgumentException('reason es requerido.');
        }

        return [
            'status' => 'handoff_required',
            'reason' => $reason,
            'summary' => $summary,
        ];
    }

    private function syncToWhatsApp(Appointment $appointment, \App\Models\Patient $patient): void
    {
        try {
            if (filled($patient->phone)) {
                app(AppointmentWorkflowService::class)->syncToCalendar($appointment);

                \Illuminate\Support\Facades\Log::info('Cita de voz sincronizada con calendario.', [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo sincronizar cita de voz con calendario.', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
