<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelnyxVoiceService
{
    public function answer(string $callControlId): void
    {
        $this->post($callControlId, 'answer');
    }

    public function gatherUsingSpeak(string $callControlId, string $text): void
    {
        $this->post($callControlId, 'gather_using_speak', [
            'payload' => $text,
            'voice' => (string) config('services.telnyx.voice', 'female'),
            'language' => (string) config('services.telnyx.language', 'es-MX'),
            'minimum_digits' => 1,
            'maximum_digits' => 1,
            'timeout_millis' => 10000,
            'valid_digits' => '1234567890',
        ]);
    }

    public function hangup(string $callControlId): void
    {
        $this->post($callControlId, 'hangup');
    }

    private function post(string $callControlId, string $action, array $payload = []): void
    {
        $apiKey = config('services.telnyx.api_key');

        if (! $apiKey) {
            Log::warning('TELNYX_API_KEY no configurado; no se envio accion Telnyx.', [
                'call_control_id' => $callControlId,
                'action' => $action,
            ]);

            return;
        }

        try {
            Http::withToken((string) $apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post(rtrim((string) config('services.telnyx.api_url'), '/') . "/calls/{$callControlId}/actions/{$action}", $payload)
                ->throw();
        } catch (\Throwable $e) {
            Log::error('Error ejecutando accion Telnyx.', [
                'call_control_id' => $callControlId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
