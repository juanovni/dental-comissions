<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\SocialIdentity;
use Illuminate\Support\Str;

class VoicePatientResolver
{
    public function find(string $phoneE164): ?Patient
    {
        $phone = $this->normalize($phoneE164);

        $patient = Patient::where('phone', $phone)->first();

        if ($patient) {
            return $patient;
        }

        $identity = SocialIdentity::where('phone', $phone)->first();

        return $identity?->patient;
    }

    public function findOrCreate(string $name, string $phoneE164): Patient
    {
        $phone = $this->normalize($phoneE164);

        $existing = $this->find($phoneE164);

        if ($existing) {
            return $existing;
        }

        return Patient::create([
            'full_name' => $name,
            'normalized_name' => Str::of($name)->lower()->ascii()->squish()->toString(),
            'phone' => $phone,
            'notes' => 'Creado por Pity Voice',
        ]);
    }

    private function normalize(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
