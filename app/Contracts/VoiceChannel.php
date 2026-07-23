<?php

namespace App\Contracts;

use App\Enums\VoiceChannelType;
use Illuminate\Http\Request;

interface VoiceChannel
{
    public function name(): VoiceChannelType;

    public function verifyWebhook(Request $request): bool;

    public function parseIncomingEvent(Request $request): array;

    public function createRealtimeSession(array $callData): array;
}
