<?php

namespace App\Services;

use App\Models\AppointmentSlotHold;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VoiceAppointmentHoldService
{
    public function create(int $doctorId, int $procedureId, string $startsAt, string $phoneE164): array
    {
        $duration = app(SocialCrmSettingsService::class)->appointmentSlotDuration();
        $holdMinutes = app(SocialCrmSettingsService::class)->appointmentSlotHoldMinutes();

        $start = Carbon::parse($startsAt);
        $end = $start->copy()->addMinutes($duration);
        $expiresAt = now()->addMinutes($holdMinutes);

        if (! app(AppointmentAvailabilityService::class)->isSlotAvailableForDoctor(
            Professional::findOrFail($doctorId),
            $start,
            $end,
        )) {
            throw new \RuntimeException('El horario seleccionado ya no esta disponible.');
        }

        $hold = AppointmentSlotHold::create([
            'doctor_id' => $doctorId,
            'procedure_id' => $procedureId,
            'starts_at' => $start,
            'ends_at' => $end,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'metadata' => ['source' => 'pity_voice', 'phone' => $phoneE164],
        ]);

        return [
            'hold_token' => $this->tokenForHold($hold),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function consume(string $holdToken): AppointmentSlotHold
    {
        $hold = $this->resolveHold($holdToken);

        if ($hold->status !== 'active') {
            throw new \RuntimeException('El hold ya fue consumido o cancelado.');
        }

        if ($hold->expires_at && $hold->expires_at->isPast()) {
            throw new \RuntimeException('El hold ha expirado. Solicita un nuevo horario.');
        }

        $hold->update(['status' => 'consumed']);

        return $hold;
    }

    public function release(string $holdToken): void
    {
        $hold = $this->resolveHold($holdToken);

        if ($hold->status === 'active') {
            $hold->update(['status' => 'released']);
        }
    }

    public function tokenForHold(AppointmentSlotHold $hold): string
    {
        return 'voice_' . $hold->id . '_' . Str::random(16);
    }

    public function resolveHold(string $token): AppointmentSlotHold
    {
        $parts = explode('_', $token);

        if (count($parts) < 3 || $parts[0] !== 'voice') {
            throw new \InvalidArgumentException('Token de hold invalido.');
        }

        $holdId = (int) $parts[1];

        return AppointmentSlotHold::findOrFail($holdId);
    }
}
