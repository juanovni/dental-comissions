<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelnyxVoiceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_initiated_creates_voice_call(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-123',
                    'call_session_id' => 'session-123',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_calls', [
            'provider_call_id' => 'call-control-123',
            'channel' => VoiceChannelType::Telnyx->value,
            'provider' => 'telnyx',
            'from_phone' => '+593999999999',
            'to_phone' => '+17866870733',
            'status' => VoiceCallStatus::Started->value,
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-123/actions/answer'));
    }

    public function test_call_hangup_completes_voice_call(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-456',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'event_type' => 'call.hangup',
                'payload' => [
                    'call_control_id' => 'call-control-456',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_calls', [
            'provider_call_id' => 'call-control-456',
            'status' => VoiceCallStatus::Completed->value,
        ]);
    }

    public function test_call_answered_starts_pity_prompt(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-789',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'event_type' => 'call.answered',
                'payload' => [
                    'call_control_id' => 'call-control-789',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_events', [
            'type' => 'assistant_message',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-789/actions/gather_using_speak'));
    }
}
