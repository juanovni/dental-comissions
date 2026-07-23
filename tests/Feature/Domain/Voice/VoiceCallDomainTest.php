<?php

namespace Tests\Feature\Domain\Voice;

use App\Enums\VoiceCallStatus;
use App\Enums\VoiceChannelType;
use App\Enums\VoiceEventType;
use App\Enums\VoiceHandoffReason;
use App\Models\VoiceCall;
use App\Models\VoiceEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceCallDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_voice_call(): void
    {
        $call = VoiceCall::factory()->create();

        $this->assertDatabaseHas('voice_calls', [
            'id' => $call->id,
            'status' => VoiceCallStatus::Started->value,
        ]);
    }

    public function test_attaches_events_to_call(): void
    {
        $call = VoiceCall::factory()->create();
        $event = VoiceEvent::factory()->create([
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::SessionStarted,
        ]);

        $this->assertTrue($call->events->contains($event));
    }

    public function test_marks_call_as_handoff_required(): void
    {
        $call = VoiceCall::factory()->handoffRequired()->create();

        $this->assertSame(VoiceCallStatus::HandoffRequired, $call->status);
    }

    public function test_completes_call(): void
    {
        $call = VoiceCall::factory()->completed()->create();

        $this->assertSame(VoiceCallStatus::Completed, $call->status);
        $this->assertNotNull($call->ended_at);
        $this->assertNotNull($call->duration_seconds);
    }

    public function test_handoff_reason_is_persisted(): void
    {
        $call = VoiceCall::factory()->create([
            'status' => VoiceCallStatus::HandoffRequired,
            'handoff_reason' => VoiceHandoffReason::Pain,
        ]);

        $this->assertSame(VoiceHandoffReason::Pain, $call->handoff_reason);
    }

    public function test_provider_event_id_prevents_duplicates(): void
    {
        $call = VoiceCall::factory()->create();

        VoiceEvent::create([
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::SessionStarted,
            'provider_event_id' => 'evt_1',
            'payload' => ['msg' => 'first'],
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        VoiceEvent::create([
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::SessionStarted,
            'provider_event_id' => 'evt_1',
            'payload' => ['msg' => 'duplicate'],
        ]);
    }

    public function test_multiple_events_without_provider_event_id_are_allowed(): void
    {
        $call = VoiceCall::factory()->create();

        VoiceEvent::create([
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::ToolCalled,
            'payload' => ['tool' => 'search'],
        ]);

        VoiceEvent::create([
            'voice_call_id' => $call->id,
            'type' => VoiceEventType::ToolResult,
            'payload' => ['result' => 'ok'],
        ]);

        $this->assertCount(2, $call->events);
    }

    public function test_call_belongs_to_patient(): void
    {
        $patient = \App\Models\Patient::factory()->create();
        $call = VoiceCall::factory()->create(['patient_id' => $patient->id]);

        $this->assertTrue($call->patient->is($patient));
    }

    public function test_channel_enum_is_cast(): void
    {
        $call = VoiceCall::factory()->create(['channel' => VoiceChannelType::WebTest]);

        $this->assertInstanceOf(VoiceChannelType::class, $call->channel);
        $this->assertSame(VoiceChannelType::WebTest, $call->channel);
    }
}
