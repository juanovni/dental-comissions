<?php

namespace App\Services\VoiceChannels;

use App\Contracts\VoiceChannel;
use App\Enums\VoiceChannelType;
use Illuminate\Http\Request;

class WebTestVoiceChannel implements VoiceChannel
{
    public function name(): VoiceChannelType
    {
        return VoiceChannelType::WebTest;
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function parseIncomingEvent(Request $request): array
    {
        return [
            'type' => 'user_message',
            'payload' => $request->all(),
        ];
    }

    public function createRealtimeSession(array $callData): array
    {
        return [
            'session_id' => 'simulated-' . str()->random(16),
            'channel' => $this->name()->value,
            'expires_at' => now()->addHour()->toIso8601String(),
        ];
    }
}
