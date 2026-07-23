<?php

namespace App\Services;

use App\Models\Procedure;
use Illuminate\Support\Str;

class AppointmentProcedureResolver
{
    public function defaultProcedure(): ?Procedure
    {
        $settingId = app(SocialCrmSettingsService::class)->get('social_appointment_default_procedure_id');

        if ($settingId) {
            $procedure = Procedure::query()
                ->whereKey((int) $settingId)
                ->where('is_active', true)
                ->first();

            if ($procedure) {
                return $procedure;
            }
        }

        return Procedure::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->where('name', 'ilike', '%valoracion%')
                    ->orWhere('name', 'ilike', '%valoración%')
                    ->orWhere('name', 'ilike', '%consulta%')
                    ->orWhere('code', 'ilike', '%VAL%')
                    ->orWhere('code', 'ilike', '%CON%');
            })
            ->orderBy('id')
            ->first();
    }

    public function findByName(?string $procedureName): ?Procedure
    {
        if (blank($procedureName)) {
            return null;
        }

        return Procedure::query()
            ->where(function ($query) use ($procedureName): void {
                $query
                    ->where('name', 'ilike', "%{$procedureName}%")
                    ->orWhere('code', 'ilike', "%{$procedureName}%");
            })
            ->where('is_active', true)
            ->first()
            ?? $this->findByContainedName($procedureName)
            ?? $this->findByCommonTranscriptionError($procedureName);
    }

    private function findByContainedName(string $procedureName): ?Procedure
    {
        $normalizedInput = $this->normalize($procedureName);

        return Procedure::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->first(function (Procedure $procedure) use ($normalizedInput): bool {
                $normalizedName = $this->normalize($procedure->name);
                $normalizedCode = $this->normalize($procedure->code);

                return ($normalizedName !== '' && str_contains($normalizedInput, $normalizedName))
                    || ($normalizedCode !== '' && str_contains($normalizedInput, $normalizedCode));
            });
    }

    private function findByCommonTranscriptionError(string $procedureName): ?Procedure
    {
        $normalizedInput = $this->normalize($procedureName);

        if (! str_contains($normalizedInput, 'lancamiento') && ! str_contains($normalizedInput, 'lanzamiento')) {
            return null;
        }

        return Procedure::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->where('name', 'ilike', '%blanqueamiento%')
                    ->orWhere('code', 'ilike', '%BLA%');
            })
            ->orderBy('id')
            ->first();
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($value))) ?? '');
    }

    public function resolveForBooking(?string $procedureName): array
    {
        $procedure = $this->findByName($procedureName);

        if ($procedure) {
            return ['procedure' => $procedure, 'is_default' => false];
        }

        if (blank($procedureName)) {
            $default = $this->defaultProcedure();

            if ($default) {
                return ['procedure' => $default, 'is_default' => true];
            }
        }

        return ['procedure' => null, 'is_default' => false];
    }
}
