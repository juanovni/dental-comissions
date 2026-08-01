<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\SocialIdentity;
use Illuminate\Support\Str;

class VoicePatientResolver
{
    public function find(string $phoneE164): ?Patient
    {
        $phones = $this->phoneVariants($phoneE164);

        $patient = Patient::whereIn('phone', $phones)->first();

        if ($patient) {
            return $patient;
        }

        $digits = $this->digits($phoneE164);
        $identity = SocialIdentity::query()
            ->whereIn('phone', $phones)
            ->when($digits !== '', fn ($query) => $query->orWhere('normalized_phone', $digits))
            ->first();

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

    /**
     * @return array<int, string>
     */
    private function phoneVariants(string $phone): array
    {
        $normalized = $this->normalize($phone);
        $digits = $this->digits($phone);

        return array_values(array_unique(array_filter([
            $normalized,
            $digits,
            $digits !== '' ? '+'.$digits : null,
        ])));
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
