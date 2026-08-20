<?php

namespace App\Services;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Models\VoiceCall;
use App\Models\VoiceEvent;
use App\Support\TenantContext;

class VoiceSessionService
{
    public function startCall(string $fromPhone, VoiceChannelType $channel, ?string $provider = null): VoiceCall
    {
        $call = VoiceCall::create([
            'clinic_id' => app(TenantContext::class)->id(),
            'from_phone' => $fromPhone,
            'channel' => $channel,
            'provider' => $provider ?? $channel->value,
            'status' => VoiceCallStatus::Started,
            'started_at' => now(),
        ]);

        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::SessionStarted,
            'payload' => ['channel' => $channel->value, 'provider' => $provider],
        ]);

        return $call;
    }

    public function addMessage(VoiceCall $call, VoiceEventType $type, string $message, ?array $extra = null): VoiceEvent
    {
        $payload = array_merge(['message' => $message], $extra ?? []);

        $event = $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => $type,
            'payload' => $payload,
        ]);

        $this->appendToTranscript($call, $type, $message);

        if ($call->status === VoiceCallStatus::Started) {
            $call->update(['status' => VoiceCallStatus::InProgress]);
        }

        return $event;
    }

    public function addToolCall(VoiceCall $call, string $toolName, array $arguments, array $result): VoiceEvent
    {
        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::ToolCalled,
            'payload' => [
                'tool' => $toolName,
                'arguments' => $arguments,
            ],
        ]);

        $resultEvent = $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::ToolResult,
            'payload' => [
                'tool' => $toolName,
                'result' => $result,
            ],
        ]);

        return $resultEvent;
    }

    public function endCall(VoiceCall $call, VoiceCallStatus $status = VoiceCallStatus::Completed): void
    {
        $call->update([
            'status' => $status,
            'ended_at' => now(),
            'duration_seconds' => (int) ($call->started_at?->diffInSeconds(now()) ?? 0),
        ]);

        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::SessionEnded,
            'payload' => ['status' => $status->value],
        ]);
    }

    public function markHandoff(VoiceCall $call, string $reason, ?string $summary = null): void
    {
        $call->update([
            'status' => VoiceCallStatus::HandoffRequired,
            'handoff_reason' => $reason,
        ]);

        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::HandoffRequested,
            'payload' => ['reason' => $reason, 'summary' => $summary],
        ]);
    }

    public function setError(VoiceCall $call, string $error): void
    {
        $call->update([
            'status' => VoiceCallStatus::Failed,
            'last_error' => $error,
        ]);

        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::Error,
            'payload' => ['error' => $error],
        ]);
    }

    public function linkAppointment(VoiceCall $call, int $appointmentId): void
    {
        $call->update([
            'appointment_id' => $appointmentId,
            'status' => VoiceCallStatus::AppointmentScheduled,
        ]);

        $call->events()->create([
            'clinic_id' => $call->clinic_id,
            'type' => VoiceEventType::AppointmentCreated,
            'payload' => ['appointment_id' => $appointmentId],
        ]);
    }

    public function updateTranscript(VoiceCall $call, string $transcript): void
    {
        $call->update(['transcript' => $transcript]);
    }

    private function appendToTranscript(VoiceCall $call, VoiceEventType $type, string $message): void
    {
        $prefix = match ($type) {
            VoiceEventType::AssistantMessage => "Asistente: ",
            VoiceEventType::UserMessage => "Usuario: ",
            default => null,
        };

        if ($prefix === null) {
            return;
        }

        $current = $call->transcript ?? '';
        $line = $prefix . $message;
        $updated = $current === '' ? $line : $current . "\n" . $line;

        $call->update(['transcript' => $updated]);
    }
}
