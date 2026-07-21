<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Models\VoiceCall;
use App\Services\VoiceAiService;
use App\Services\VoiceSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
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

    public function test_call_answered_speaks_pity_prompt_and_stores_event(): void
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

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-789/actions/speak'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-789/actions/transcription_start'));
    }

    public function test_speak_ended_starts_transcription(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-006',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-790',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-007',
                'event_type' => 'call.speak.ended',
                'payload' => [
                    'call_control_id' => 'call-control-790',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-007',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-790/actions/transcription_start'));
    }

    public function test_speak_ended_on_completed_call_calls_hangup(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-008',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-800',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        VoiceCall::query()->where('provider_call_id', 'call-control-800')->update([
            'status' => VoiceCallStatus::Completed->value,
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-009',
                'event_type' => 'call.speak.ended',
                'payload' => [
                    'call_control_id' => 'call-control-800',
                ],
            ],
        ]);

        $response->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-800/actions/hangup'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-800/actions/transcription_start'));
    }

    public function test_speak_ended_on_handoff_call_calls_hangup(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-010',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-900',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        VoiceCall::query()->where('provider_call_id', 'call-control-900')->update([
            'status' => VoiceCallStatus::HandoffRequired->value,
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-011',
                'event_type' => 'call.speak.ended',
                'payload' => [
                    'call_control_id' => 'call-control-900',
                ],
            ],
        ]);

        $response->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-900/actions/hangup'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-900/actions/transcription_start'));
    }

    public function test_final_transcription_is_processed_by_voice_ai_and_spoken(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $sessions = app(VoiceSessionService::class);

        $this->mock(VoiceAiService::class, function (MockInterface $mock) use ($sessions): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(fn (int $callId, string $message): bool => $message === 'Quiero una cita para limpieza')
                ->andReturnUsing(function (int $callId, string $message) use ($sessions): array {
                    $call = VoiceCall::query()->findOrFail($callId);

                    $sessions->addMessage($call, VoiceEventType::UserMessage, $message);
                    $sessions->addMessage($call, VoiceEventType::AssistantMessage, 'Claro, te ayudo a buscar disponibilidad.');

                    return [
                        'message' => 'Claro, te ayudo a buscar disponibilidad.',
                        'ended' => false,
                        'handoff' => false,
                    ];
                });
        });

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-040',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-333',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-041',
                'event_type' => 'call.transcription',
                'payload' => [
                    'call_control_id' => 'call-control-333',
                    'transcription_data' => [
                        'confidence' => 0.97,
                        'is_final' => true,
                        'transcript' => 'Quiero una cita para limpieza',
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-041',
        ]);

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::UserMessage->value,
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-333/actions/transcription_stop'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-333/actions/speak'));
    }

    public function test_partial_transcription_is_stored_but_not_processed(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-050',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-444',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $this->mock(VoiceAiService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-051',
                'event_type' => 'call.transcription',
                'payload' => [
                    'call_control_id' => 'call-control-444',
                    'transcription_data' => [
                        'confidence' => 0.72,
                        'is_final' => false,
                        'transcript' => 'Quiero una',
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('voice_events', [
            'type' => VoiceEventType::CallEvent->value,
            'provider_event_id' => 'evt-051',
        ]);

        $this->assertDatabaseMissing('voice_events', [
            'type' => VoiceEventType::UserMessage->value,
        ]);
    }

    public function test_final_transcription_does_not_speak_if_call_ended_while_processing(): void
    {
        config(['services.telnyx.api_key' => 'test-key']);
        Http::fake(['api.telnyx.com/*' => Http::response(['ok' => true])]);

        $this->mock(VoiceAiService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (int $callId): array {
                    VoiceCall::query()->whereKey($callId)->update([
                        'status' => VoiceCallStatus::Completed->value,
                        'ended_at' => now(),
                    ]);

                    return [
                        'message' => 'Encontré horarios disponibles.',
                        'ended' => false,
                        'handoff' => false,
                    ];
                });
        });

        $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-060',
                'event_type' => 'call.initiated',
                'payload' => [
                    'call_control_id' => 'call-control-555',
                    'from' => '+593999999999',
                    'to' => '+17866870733',
                ],
            ],
        ]);

        $response = $this->postJson('/webhook/telnyx/voice/events', [
            'data' => [
                'id' => 'evt-061',
                'event_type' => 'call.transcription',
                'payload' => [
                    'call_control_id' => 'call-control-555',
                    'transcription_data' => [
                        'confidence' => 0.97,
                        'is_final' => true,
                        'transcript' => 'Quiero una cita para limpieza',
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-555/actions/transcription_stop'));
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/calls/call-control-555/actions/speak'));
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
