<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Enums\VoiceHandoffReason;
use App\Models\Appointment;
use App\Models\VoiceCall;
use App\Services\VoiceSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private VoiceSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VoiceSessionService::class);
    }

    public function test_start_call_creates_call_and_session_event(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->assertDatabaseHas('voice_calls', [
            'id' => $call->id,
            'from_phone' => '+593999999999',
            'channel' => VoiceChannelType::WebTest->value,
            'status' => VoiceCallStatus::Started->value,
        ]);

        $this->assertDatabaseHas('voice_events', [
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::SessionStarted->value,
        ]);
    }

    public function test_add_message_creates_event_and_updates_status(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->service->addMessage($call, VoiceEventType::UserMessage, 'Hola, quiero agendar una cita.');

        $this->assertDatabaseHas('voice_events', [
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::UserMessage->value,
        ]);

        $call->refresh();
        $this->assertEquals(VoiceCallStatus::InProgress, $call->status);
    }

    public function test_add_tool_call_creates_two_events(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->service->addToolCall($call, 'identify_patient', ['phone_e164' => '+593999999999'], ['found' => true]);

        $this->assertDatabaseHas('voice_events', [
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::ToolCalled->value,
        ]);

        $this->assertDatabaseHas('voice_events', [
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::ToolResult->value,
        ]);
    }

    public function test_end_call_updates_duration(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->travel(30)->seconds();

        $this->service->endCall($call);

        $call->refresh();
        $this->assertEquals(VoiceCallStatus::Completed, $call->status);
        $this->assertNotNull($call->ended_at);
        $this->assertGreaterThanOrEqual(29, $call->duration_seconds);
    }

    public function test_mark_handoff_updates_status(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->service->markHandoff($call, 'pain', 'Paciente reporta dolor intenso.');

        $call->refresh();
        $this->assertEquals(VoiceCallStatus::HandoffRequired, $call->status);
        $this->assertEquals(VoiceHandoffReason::Pain, $call->handoff_reason);
    }

    public function test_set_error_updates_status(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->service->setError($call, 'Tool call timeout');

        $call->refresh();
        $this->assertEquals(VoiceCallStatus::Failed, $call->status);
        $this->assertEquals('Tool call timeout', $call->last_error);
    }

    public function test_link_appointment_updates_call(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $appointment = Appointment::factory()->create();

        $this->service->linkAppointment($call, $appointment->id);

        $call->refresh();
        $this->assertEquals(VoiceCallStatus::AppointmentScheduled, $call->status);
        $this->assertNotNull($call->appointment_id);
    }

    public function test_transcript_is_built_from_messages(): void
    {
        $call = $this->service->startCall('+593999999999', VoiceChannelType::WebTest);

        $this->service->addMessage($call, VoiceEventType::UserMessage, 'Quiero una limpieza.');
        $this->service->addMessage($call, VoiceEventType::AssistantMessage, 'Claro, tengo disponibilidad.');

        $call->refresh();
        $this->assertStringContainsString('Usuario: Quiero una limpieza.', $call->transcript);
        $this->assertStringContainsString('Asistente: Claro, tengo disponibilidad.', $call->transcript);
    }
}
