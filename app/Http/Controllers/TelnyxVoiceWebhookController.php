<?php

namespace App\Http\Controllers;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Models\VoiceCall;
use App\Services\TelnyxVoiceService;
use App\Services\VoiceAiService;
use App\Services\VoiceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelnyxVoiceWebhookController extends Controller
{
    public function __construct(
        private TelnyxVoiceService $telnyx,
        private VoiceAiService $voiceAi,
        private VoiceSessionService $sessions,
    ) {}

    public function events(Request $request): JsonResponse
    {
        $eventType = (string) $request->input('data.event_type', $request->input('event_type', 'unknown'));
        $payload = $request->input('data.payload', []);

        if (! is_array($payload)) {
            $payload = [];
        }

        $providerCallId = $this->providerCallId($payload, $request->all());

        match ($eventType) {
            'call.initiated' => $this->recordInitiatedCall($providerCallId, $payload, $request->all()),
            'call.answered' => $this->startPityConversation($providerCallId, $payload),
            'call.gather.ended' => $this->handleGatherEnded($providerCallId, $payload),
            'call.hangup' => $this->recordEndedCall($providerCallId, $eventType, $payload),
            default => $this->recordCallEvent($providerCallId, $eventType, $payload),
        };

        return response()->json(['ok' => true]);
    }

    private function recordInitiatedCall(?string $providerCallId, array $payload, array $rawEvent): void
    {
        if (! $providerCallId) {
            Log::warning('Evento Telnyx call.initiated sin call id.', ['payload' => $payload]);
            return;
        }

        VoiceCall::query()->updateOrCreate(
            ['provider_call_id' => $providerCallId],
            [
                'channel' => VoiceChannelType::Telnyx->value,
                'provider' => 'telnyx',
                'from_phone' => (string) ($payload['from'] ?? 'unknown'),
                'to_phone' => $payload['to'] ?? null,
                'status' => VoiceCallStatus::Started->value,
                'started_at' => now(),
                'metadata' => [
                    'telnyx_call_control_id' => $payload['call_control_id'] ?? null,
                    'telnyx_call_session_id' => $payload['call_session_id'] ?? null,
                    'last_telnyx_event' => 'call.initiated',
                    'last_telnyx_payload' => $rawEvent,
                ],
            ],
        );

        $callControlId = $payload['call_control_id'] ?? null;

        if (is_string($callControlId) && $callControlId !== '') {
            $this->telnyx->answer($callControlId);
        }
    }

    private function startPityConversation(?string $providerCallId, array $payload): void
    {
        $call = $this->findCall($providerCallId, $payload);
        $callControlId = $this->callControlId($call, $payload);

        if (! $call || ! $callControlId) {
            return;
        }

        $greeting = 'Hola, soy Pity, la recepcionista virtual de OdonCRM. En que puedo ayudarte?';

        if (! $call->events()->where('type', VoiceEventType::AssistantMessage->value)->exists()) {
            $this->sessions->addMessage($call, VoiceEventType::AssistantMessage, $greeting, [
                'provider' => 'telnyx',
            ]);
        }

        $this->telnyx->gatherUsingSpeak($callControlId, $greeting);
    }

    private function handleGatherEnded(?string $providerCallId, array $payload): void
    {
        $call = $this->findCall($providerCallId, $payload);
        $callControlId = $this->callControlId($call, $payload);

        if (! $call || ! $callControlId) {
            return;
        }

        $speechText = $this->speechText($payload);

        if ($speechText === '') {
            $reply = 'Disculpa, no logre escucharte bien. Puedes repetirlo?';
            $this->sessions->addMessage($call, VoiceEventType::AssistantMessage, $reply, ['provider' => 'telnyx']);
            $this->telnyx->gatherUsingSpeak($callControlId, $reply);

            return;
        }

        $result = $this->voiceAi->sendMessage($call->id, $speechText);
        $reply = trim((string) ($result['message'] ?? ''));

        if ($reply === '') {
            $reply = 'Disculpa, tuve un problema procesando tu solicitud. Puedes repetirlo?';
        }

        if (($result['ended'] ?? false) === true) {
            $this->telnyx->gatherUsingSpeak($callControlId, $reply);
            return;
        }

        $this->telnyx->gatherUsingSpeak($callControlId, $reply);
    }

    private function recordEndedCall(?string $providerCallId, string $eventType, array $payload): void
    {
        $call = $this->findCall($providerCallId, $payload);

        if (! $call) {
            return;
        }

        $metadata = $call->metadata ?? [];
        $metadata['last_telnyx_event'] = $eventType;
        $metadata['last_telnyx_payload'] = $payload;

        $call->update([
            'status' => VoiceCallStatus::Completed->value,
            'ended_at' => now(),
            'duration_seconds' => (int) ($call->started_at?->diffInSeconds(now()) ?? 0),
            'metadata' => $metadata,
        ]);
    }

    private function recordCallEvent(?string $providerCallId, string $eventType, array $payload): void
    {
        $call = $this->findCall($providerCallId, $payload);

        if (! $call) {
            return;
        }

        $metadata = $call->metadata ?? [];
        $metadata['last_telnyx_event'] = $eventType;
        $metadata['last_telnyx_payload'] = $payload;

        $call->update(['metadata' => $metadata]);
    }

    private function findCall(?string $providerCallId, array $payload): ?VoiceCall
    {
        if ($providerCallId) {
            $call = VoiceCall::query()->where('provider_call_id', $providerCallId)->first();

            if ($call) {
                return $call;
            }
        }

        $callSessionId = $payload['call_session_id'] ?? null;

        if (! $callSessionId) {
            return null;
        }

        return VoiceCall::query()
            ->where('provider', 'telnyx')
            ->where('metadata->telnyx_call_session_id', $callSessionId)
            ->latest('id')
            ->first();
    }

    private function providerCallId(array $payload, array $rawEvent): ?string
    {
        return $payload['call_control_id']
            ?? $payload['call_leg_id']
            ?? $rawEvent['data']['id']
            ?? null;
    }

    private function callControlId(?VoiceCall $call, array $payload): ?string
    {
        $metadata = $call?->metadata ?? [];

        return $payload['call_control_id']
            ?? $metadata['telnyx_call_control_id']
            ?? $call?->provider_call_id;
    }

    private function speechText(array $payload): string
    {
        $candidates = [
            $payload['speech_result']['transcript'] ?? null,
            $payload['speech_result']['text'] ?? null,
            $payload['speech']['transcript'] ?? null,
            $payload['transcription'] ?? null,
            $payload['result'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }
}
