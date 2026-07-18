<?php

namespace Tests\Feature\Domain\Voice;

use App\Models\AppointmentSlotHold;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Services\VoiceAppointmentHoldService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceToolsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'test-voice-token';

    private function nextClinicSlot(): string
    {
        return now()->next('Monday')->setTime(10, 0)->format('Y-m-d H:i:s');
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.voice.tool_token' => $this->validToken]);
    }

    public function test_identify_patient_finds_by_phone(): void
    {
        $patient = Patient::factory()->create(['phone' => '+593999999999']);

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593999999999',
            ]);

        $response->assertOk()
            ->assertJson([
                'found' => true,
                'patient_id' => $patient->id,
                'name' => $patient->full_name,
            ]);
    }

    public function test_identify_patient_returns_not_found(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593999999998',
            ]);

        $response->assertOk()
            ->assertJson([
                'found' => false,
                'patient_id' => null,
                'name' => null,
            ]);
    }

    public function test_identify_patient_requires_phone(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/identify-patient', []);

        $response->assertStatus(422);
    }

    public function test_get_available_slots_returns_empty_when_no_slots(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/get-available-slots', []);

        $response->assertOk()
            ->assertJson([
                'procedure_found' => false,
                'slots' => [],
                'message' => 'Antes de buscar horarios necesito confirmar el procedimiento o configurar un procedimiento default de valoracion.',
            ]);
    }

    public function test_get_available_slots_requires_procedure_before_offering_slots(): void
    {
        Professional::factory()->doctor()->create(['is_active' => true]);

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/get-available-slots', [
                'preferred_date' => now()->next('Monday')->format('Y-m-d'),
                'preferred_period' => 'afternoon',
            ]);

        $response->assertOk()
            ->assertJson([
                'procedure_found' => false,
                'slots' => [],
                'message' => 'Antes de buscar horarios necesito confirmar el procedimiento o configurar un procedimiento default de valoracion.',
            ]);
    }

    public function test_get_available_slots_uses_default_procedure_for_generic_booking(): void
    {
        $doctor = Professional::factory()->doctor()->create(['is_active' => true]);
        $procedure = Procedure::factory()->create(['name' => 'Valoracion dental', 'is_active' => true]);

        \App\Models\SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_default_procedure_id'],
            ['value' => $procedure->id, 'value_type' => 'integer', 'label' => 'Default procedure', 'is_active' => true],
        );

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/get-available-slots', [
                'preferred_date' => now()->next('Monday')->format('Y-m-d'),
                'preferred_period' => 'afternoon',
            ]);

        $response->assertOk()
            ->assertJson([
                'procedure_found' => true,
                'procedure_id' => $procedure->id,
                'procedure_name' => 'Valoracion dental',
                'is_default_procedure' => true,
            ]);

        $this->assertNotEmpty($response->json('slots'));
        $this->assertSame($procedure->id, $response->json('slots.0.procedure_id'));
        $this->assertSame($doctor->id, $response->json('slots.0.doctor_id'));
    }

    public function test_get_available_slots_does_not_return_past_dates(): void
    {
        Professional::factory()->doctor()->create(['is_active' => true]);
        Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/get-available-slots', [
                'procedure_name' => 'Limpieza',
                'preferred_date' => '2023-10-30',
                'preferred_period' => 'afternoon',
            ]);

        $response->assertOk();

        foreach ($response->json('slots') as $slot) {
            $this->assertGreaterThanOrEqual(now()->startOfMinute(), \Carbon\Carbon::parse($slot['datetime']));
        }
    }

    public function test_rejects_request_without_token(): void
    {
        $response = $this->postJson('/api/voice/tools/identify-patient', [
            'phone_e164' => '+593999999999',
        ]);

        $response->assertStatus(401);
    }

    public function test_hold_slot_creates_hold(): void
    {
        $doctor = Professional::factory()->create();
        $procedure = Procedure::factory()->create();

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/hold-slot', [
                'slot_datetime' => $this->nextClinicSlot(),
                'doctor_id' => $doctor->id,
                'procedure_id' => $procedure->id,
                'phone_e164' => '+593999999999',
            ]);

        $response->assertOk();
        $this->assertArrayHasKey('hold_token', $response->json());
        $this->assertArrayHasKey('expires_at', $response->json());

        $this->assertDatabaseHas('appointment_slot_holds', [
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'status' => 'active',
        ]);
    }

    public function test_create_appointment_requires_hold_token(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/create-appointment', [
                'patient_name' => 'Maria Perez',
                'phone_e164' => '+593999999999',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_appointment_with_valid_hold(): void
    {
        $doctor = Professional::factory()->create();
        $procedure = Procedure::factory()->create();

        $holdResult = app(VoiceAppointmentHoldService::class)->create(
            doctorId: $doctor->id,
            procedureId: $procedure->id,
            startsAt: $this->nextClinicSlot(),
            phoneE164: '+593999999999',
        );

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/create-appointment', [
                'hold_token' => $holdResult['hold_token'],
                'patient_name' => 'Maria Perez',
                'phone_e164' => '+593999999999',
                'procedure_id' => $procedure->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'appointment_id',
                'status',
                'confirmation_message',
            ]);
    }

    public function test_request_handoff(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/request-handoff', [
                'reason' => 'pain',
                'summary' => 'Paciente reporta dolor intenso.',
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'handoff_required',
                'reason' => 'pain',
            ]);
    }

    public function test_hold_slot_requires_doctor_and_procedure(): void
    {
        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/hold-slot', [
                'slot_datetime' => $this->nextClinicSlot(),
            ]);

        $response->assertStatus(422);
    }

    public function test_create_appointment_creates_patient_when_not_found()
    {
        $doctor = Professional::factory()->create();
        $procedure = Procedure::factory()->create();

        $holdResult = app(VoiceAppointmentHoldService::class)->create(
            doctorId: $doctor->id,
            procedureId: $procedure->id,
            startsAt: $this->nextClinicSlot(),
            phoneE164: '+593999999999',
        );

        $response = $this->withToken($this->validToken)
            ->postJson('/api/voice/tools/create-appointment', [
                'hold_token' => $holdResult['hold_token'],
                'patient_name' => 'Nuevo Paciente Voz',
                'phone_e164' => '+593999999999',
                'procedure_id' => $procedure->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('patients', [
            'full_name' => 'Nuevo Paciente Voz',
            'phone' => '+593999999999',
        ]);
    }
}
