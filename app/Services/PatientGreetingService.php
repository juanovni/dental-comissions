<?php

namespace App\Services;

use App\Models\Patient;

class PatientGreetingService
{
    public function __construct(
        private VoicePatientResolver $patientResolver,
    ) {}

    public function resolveByPhone(string $phoneE164): ?Patient
    {
        return $this->patientResolver->find($phoneE164);
    }

    public function greeting(?Patient $patient): string
    {
        $firstName = $this->firstName($patient?->full_name);

        if ($firstName !== '') {
            return "Hola {$firstName}, soy Pity. Que gusto escucharte otra vez. ¿Quieres agendar, confirmar o cambiar una cita?";
        }

        return 'Hola, soy Pity, la recepcionista virtual de OdonCRM. ¿En qué puedo ayudarte?';
    }

    private function firstName(?string $fullName): string
    {
        $name = trim((string) $fullName);

        if ($name === '') {
            return '';
        }

        return strtok($name, ' ') ?: '';
    }
}
