<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;

class WhatsappCheckInService
{
    public function __construct(
        private PatientFlowService $patientFlowService,
    ) {}

    public function isCheckInRequest(string $body): bool
    {
        $normalized = $this->normalize($body);

        $patterns = [
            'llegue',
            'ya llegue',
            'ya llegué',
            'estoy aqui',
            'estoy aquí',
            'ya estoy',
            'llego',
            'acabo de llegar',
            'ya estoy aqui',
            'ya estoy aquí',
            'estoy en recepcion',
            'estoy en recepción',
            'ya me encuentro aqui',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function processCheckIn(WhatsappMessage $message): ?Appointment
    {
        $patient = $this->resolvePatient($message);

        if (! $patient) {
            Log::info('WhatsappCheckInService: paciente no encontrado', [
                'whatsapp_message_id' => $message->id,
            ]);

            return null;
        }

        $appointment = Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [
                AppointmentStatus::Confirmed,
                AppointmentStatus::OnTheWay,
                AppointmentStatus::Rescheduled,
            ])
            ->orderBy('scheduled_at')
            ->first();

        if (! $appointment) {
            Log::info('WhatsappCheckInService: cita activa no encontrada para hoy', [
                'patient_id' => $patient->id,
            ]);

            return null;
        }

        $this->patientFlowService->checkIn($appointment);

        Log::info('WhatsappCheckInService: check-in realizado via WhatsApp', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
        ]);

        return $appointment->fresh();
    }

    public function getConfirmationMessage(Appointment $appointment): string
    {
        $doctorName = $appointment->doctor?->name ?? 'el doctor';

        return "¡Hola {$appointment->patient?->full_name}! Tu llegada ha sido registrada. "
            ."El Dr(a). {$doctorName} te atendera en breve. Gracias por avisar.";
    }

    private function resolvePatient(WhatsappMessage $message): ?Patient
    {
        $from = $message->from_number;

        if (! $from) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $from);

        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        return Patient::query()
            ->where(function ($q) use ($phone, $from): void {
                $q->whereRaw('REGEXP_REPLACE(phone, \'[^0-9]\', \'\', \'g\') LIKE ?', ["%{$phone}"])
                    ->orWhere('phone', $from);
            })
            ->orWhere('whatsapp_phone', $from)
            ->orWhereRaw('REGEXP_REPLACE(whatsapp_phone, \'[^0-9]\', \'\', \'g\') LIKE ?', ["%{$phone}"])
            ->first();
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $text);

        return preg_replace('/[^a-z0-9\s]/', '', $text);
    }
}
