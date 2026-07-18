<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Services\VoiceAiService;
use App\Services\VoiceSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class VoiceAiBackendGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_hold_slot_must_match_previously_offered_backend_slot(): void
    {
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        app(VoiceSessionService::class)->addToolCall($call, 'get_available_slots', ['procedure_name' => 'Limpieza'], [
            'procedure_found' => true,
            'procedure_id' => 7,
            'procedure_name' => 'Limpieza',
            'slots' => [[
                'datetime' => '2026-07-20 10:00:00',
                'doctor_id' => 3,
                'procedure_id' => 7,
            ]],
        ]);

        $this->assertToolArgsAllowed('hold_slot', $call, [
            'slot_datetime' => '2026-07-20 10:00:00',
            'doctor_id' => 3,
            'procedure_id' => 7,
        ]);

        $this->assertTrue(true);
    }

    public function test_hold_slot_rejects_unoffered_backend_slot(): void
    {
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no proviene de get_available_slots');

        $this->assertToolArgsAllowed('hold_slot', $call, [
            'slot_datetime' => '2026-07-20 10:00:00',
            'doctor_id' => 3,
            'procedure_id' => 7,
        ]);
    }

    public function test_create_appointment_must_use_previously_issued_hold_token(): void
    {
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        app(VoiceSessionService::class)->addToolCall($call, 'hold_slot', ['slot_datetime' => '2026-07-20 10:00:00'], [
            'hold_token' => 'voice_1_validtoken',
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        $this->assertToolArgsAllowed('create_appointment', $call, [
            'hold_token' => 'voice_1_validtoken',
        ]);

        $this->assertTrue(true);
    }

    public function test_create_appointment_rejects_unissued_hold_token(): void
    {
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('hold_token no fue emitido');

        $this->assertToolArgsAllowed('create_appointment', $call, [
            'hold_token' => 'voice_999_fake',
        ]);
    }

    public function test_get_available_slots_prefers_backend_parsed_user_date_over_ai_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-18')); // Saturday

        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);
        app(VoiceSessionService::class)->addMessage(
            $call,
            VoiceEventType::UserMessage,
            'Quiero agendar una limpieza para este lunes en la tarde',
        );

        $method = new ReflectionMethod(VoiceAiService::class, 'normalizeToolArgs');
        $method->setAccessible(true);

        $args = $method->invoke(app(VoiceAiService::class), $call, 'get_available_slots', [
            'procedure_name' => 'limpieza',
            'preferred_date' => '2026-07-24',
            'preferred_period' => 'afternoon',
        ]);

        $this->assertSame('2026-07-20', $args['preferred_date']);
        $this->assertSame('afternoon', $args['preferred_period']);

        Carbon::setTestNow();
    }

    public function test_available_slots_reply_uses_backend_slot_labels(): void
    {
        $method = new ReflectionMethod(VoiceAiService::class, 'buildAvailableSlotsReply');
        $method->setAccessible(true);

        $reply = $method->invoke(app(VoiceAiService::class), [
            'procedure_name' => 'Limpieza dental',
            'slots' => [
                ['label' => 'lunes 20 de julio a las 1:30 PM', 'datetime' => '2026-07-20 13:30:00'],
                ['label' => 'lunes 20 de julio a las 2:15 PM', 'datetime' => '2026-07-20 14:15:00'],
                ['label' => 'lunes 20 de julio a las 3:00 PM', 'datetime' => '2026-07-20 15:00:00'],
            ],
        ]);

        $this->assertStringContainsString('Limpieza dental', $reply);
        $this->assertStringContainsString('1. lunes 20 de julio a las 1:30 PM', $reply);
        $this->assertStringNotContainsString('24 de julio', $reply);
    }

    public function test_available_slots_reply_explains_default_procedure(): void
    {
        $method = new ReflectionMethod(VoiceAiService::class, 'buildAvailableSlotsReply');
        $method->setAccessible(true);

        $reply = $method->invoke(app(VoiceAiService::class), [
            'procedure_name' => 'Valoracion dental',
            'is_default_procedure' => true,
            'slots' => [[
                'label' => 'lunes 20 de julio a las 1:30 PM',
                'datetime' => '2026-07-20 13:30:00',
            ]],
        ]);

        $this->assertStringContainsString('no tengo un procedimiento específico', $reply);
        $this->assertStringContainsString('Valoracion dental', $reply);
    }

    public function test_selects_previously_offered_slot_by_voice_time_format(): void
    {
        $call = $this->callWithOfferedSlots();

        $method = new ReflectionMethod(VoiceAiService::class, 'selectedOfferedSlotFromMessage');
        $method->setAccessible(true);

        $slot = $method->invoke(app(VoiceAiService::class), $call, '9:45 a. m.');

        $this->assertNotNull($slot);
        $this->assertSame('2026-07-23 09:45:00', $slot['datetime']);
    }

    public function test_selects_previously_offered_slot_by_spoken_time_format(): void
    {
        $call = $this->callWithOfferedSlots();

        $method = new ReflectionMethod(VoiceAiService::class, 'selectedOfferedSlotFromMessage');
        $method->setAccessible(true);

        $slot = $method->invoke(app(VoiceAiService::class), $call, 'a las 9 y 45');

        $this->assertNotNull($slot);
        $this->assertSame('2026-07-23 09:45:00', $slot['datetime']);
    }

    public function test_selects_previously_offered_slot_by_option_number(): void
    {
        $call = $this->callWithOfferedSlots();

        $method = new ReflectionMethod(VoiceAiService::class, 'selectedOfferedSlotFromMessage');
        $method->setAccessible(true);

        $slot = $method->invoke(app(VoiceAiService::class), $call, '2');

        $this->assertNotNull($slot);
        $this->assertSame('2026-07-23 09:45:00', $slot['datetime']);
    }

    public function test_selected_incomplete_slot_asks_for_procedure_instead_of_saying_occupied(): void
    {
        $service = app(VoiceAiService::class);
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        app(VoiceSessionService::class)->addToolCall($call, 'get_available_slots', [], [
            'procedure_found' => null,
            'procedure_id' => null,
            'procedure_name' => null,
            'slots' => [[
                'datetime' => '2026-07-23 09:45:00',
                'label' => 'jueves 23 de julio a las 9:45 a. m.',
                'doctor_id' => 3,
                'procedure_id' => null,
            ]],
        ]);

        $response = $service->sendMessage($call->id, '9:45 a. m.');

        $this->assertStringContainsString('necesito confirmar el procedimiento', $response['message']);
        $this->assertStringNotContainsString('ocupo', $response['message']);
        $this->assertStringNotContainsString('ocup', $response['message']);
    }

    private function assertToolArgsAllowed(string $tool, mixed $call, array $args): void
    {
        $method = new ReflectionMethod(VoiceAiService::class, 'assertToolArgsAllowedByBackendHistory');
        $method->setAccessible(true);
        $method->invoke(app(VoiceAiService::class), $call, $tool, $args);
    }

    private function callWithOfferedSlots(): mixed
    {
        $call = app(VoiceSessionService::class)->startCall('+593999999999', VoiceChannelType::WebTest);

        app(VoiceSessionService::class)->addToolCall($call, 'get_available_slots', ['procedure_name' => 'Limpieza'], [
            'procedure_found' => true,
            'procedure_id' => 7,
            'procedure_name' => 'Limpieza',
            'slots' => [
                [
                    'datetime' => '2026-07-23 09:00:00',
                    'label' => 'jueves 23 de julio a las 9:00 a. m.',
                    'doctor_id' => 3,
                    'procedure_id' => 7,
                ],
                [
                    'datetime' => '2026-07-23 09:45:00',
                    'label' => 'jueves 23 de julio a las 9:45 a. m.',
                    'doctor_id' => 3,
                    'procedure_id' => 7,
                ],
                [
                    'datetime' => '2026-07-23 10:30:00',
                    'label' => 'jueves 23 de julio a las 10:30 a. m.',
                    'doctor_id' => 3,
                    'procedure_id' => 7,
                ],
            ],
        ]);

        return $call->refresh();
    }
}
