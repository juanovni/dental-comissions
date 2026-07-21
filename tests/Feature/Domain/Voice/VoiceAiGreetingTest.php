<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Models\Patient;
use App\Services\VoiceAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceAiGreetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_voice_simulator_uses_patient_name_when_phone_is_known(): void
    {
        $patient = Patient::factory()->create([
            'full_name' => 'Maria Perez',
            'phone' => '+593999999999',
        ]);

        $callId = null;
        $result = app(VoiceAiService::class)->startConversation('+593999999999', $callId);

        $this->assertSame('Hola Maria, soy Pity. Que gusto escucharte otra vez. ¿Quieres agendar, confirmar o cambiar una cita?', $result['message']);

        $this->assertDatabaseHas('voice_calls', [
            'id' => $callId,
            'patient_id' => $patient->id,
            'channel' => VoiceChannelType::WebTest->value,
        ]);

        $this->assertDatabaseHas('voice_events', [
            'voice_call_id' => $callId,
            'type' => VoiceEventType::AssistantMessage->value,
            'payload->message' => 'Hola Maria, soy Pity. Que gusto escucharte otra vez. ¿Quieres agendar, confirmar o cambiar una cita?',
        ]);
    }

    public function test_web_voice_simulator_uses_generic_greeting_when_phone_is_unknown(): void
    {
        $callId = null;
        $result = app(VoiceAiService::class)->startConversation('+593999999998', $callId);

        $this->assertSame('Hola, soy Pity, la recepcionista virtual de OdonCRM. ¿En qué puedo ayudarte?', $result['message']);
    }
}
