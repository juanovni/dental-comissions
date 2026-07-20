<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelnyxVoiceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_initiated_creates_voice_call_and_event(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-001',
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

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-001',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-123/actions/answer'));
    }

    public function test_call_hangup_completes_voice_call_and_stores_event(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-002',
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
                'id' => 'evt-003',
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

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-003',
        ]);
    }

    public function test_call_answered_starts_pity_prompt_and_stores_event(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-004',
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
                'id' => 'evt-005',
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

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-005',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-789/actions/gather_using_speak'));
    }

    public function test_duplicate_event_id_is_rejected(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-010',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-999',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-010',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-999',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['duplicate' => true]);

        $this->assertDatabaseCount('voice_calls', 1);
        $this->assertDatabaseCount('voice_events', 1);
    }

    public function test_debug_logging_only_when_telnyx_debug_enabled(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        config(['services.telnyx.debug' => true]);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'Webhook Telnyx recibido.'));

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-020',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-111',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);
    }

    public function test_voice_call_metadata_does_not_contain_full_payload(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-030',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-222',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $call = \App\Models\VoiceCall::query()
            ->where('provider_call_id', 'call-control-222')
            ->first();

        $this->assertNotNull($call);
        $this->assertArrayNotHasKey('last_telnyx_payload', $call->metadata ?? []);
    }
}
