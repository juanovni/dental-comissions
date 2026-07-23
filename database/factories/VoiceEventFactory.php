<?php

namespace Database\Factories;

use App\Enums\VoiceEventType;
use App\Models\VoiceCall;
use App\Models\VoiceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoiceEventFactory extends Factory
{
    protected $model = VoiceEvent::class;

    public function definition(): array
    {
        return [
            'voice_call_id' => VoiceCall::factory(),
            'type' => VoiceEventType::ToolCalled,
            'payload' => ['tool' => 'test', 'input' => 'test data'],
        ];
    }
}
