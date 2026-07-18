<?php

namespace App\Services;

use App\Models\Procedure;

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
            ->first();
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
