<?php

namespace Tests\Feature\Domain\Voice;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceToolsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function clinic(string $slug, string $token): Clinic
    {
        return Clinic::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'subdomain' => $slug,
            'primary_domain' => $slug.'.localhost',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
            'settings' => [
                'integrations' => [
                    'voice' => ['tool_token' => $token],
                ],
            ],
        ]);
    }

    public function test_clinic_token_resolves_its_own_patient(): void
    {
        $clinicA = $this->clinic('clinic-a', 'token-a');
        $patient = Patient::factory()->create(['clinic_id' => $clinicA->id, 'phone' => '+593000000001']);

        $this->withToken('token-a')
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593000000001',
            ])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'patient_id' => $patient->id,
                'name' => $patient->full_name,
            ]);
    }

    public function test_clinic_token_does_not_see_patient_of_another_clinic(): void
    {
        $clinicA = $this->clinic('clinic-a', 'token-a');
        $clinicB = $this->clinic('clinic-b', 'token-b');

        Patient::factory()->create(['clinic_id' => $clinicA->id, 'phone' => '+593000000001']);
        Patient::factory()->create(['clinic_id' => $clinicB->id, 'phone' => '+593000000002']);

        $this->withToken('token-a')
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593000000002',
            ])
            ->assertOk()
            ->assertJson([
                'found' => false,
                'patient_id' => null,
            ]);
    }

    public function test_second_clinic_token_resolves_its_own_patient(): void
    {
        $clinicA = $this->clinic('clinic-a', 'token-a');
        $clinicB = $this->clinic('clinic-b', 'token-b');

        Patient::factory()->create(['clinic_id' => $clinicA->id, 'phone' => '+593000000001']);
        $patientB = Patient::factory()->create(['clinic_id' => $clinicB->id, 'phone' => '+593000000002']);

        $this->withToken('token-b')
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593000000002',
            ])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'patient_id' => $patientB->id,
                'name' => $patientB->full_name,
            ]);
    }

    public function test_hold_slot_rejects_doctor_from_another_clinic(): void
    {
        $clinicA = $this->clinic('clinic-a', 'token-a');
        $clinicB = $this->clinic('clinic-b', 'token-b');

        $procedureA = Procedure::factory()->create(['clinic_id' => $clinicA->id, 'is_active' => true]);
        $doctorB = Professional::factory()->doctor()->create(['clinic_id' => $clinicB->id, 'is_active' => true]);

        $this->withToken('token-a')
            ->postJson('/api/voice/tools/hold-slot', [
                'slot_datetime' => now()->next('Monday')->setTime(10, 0)->format('Y-m-d H:i:s'),
                'doctor_id' => $doctorB->id,
                'procedure_id' => $procedureA->id,
                'phone_e164' => '+593000000001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['doctor_id']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withToken('invalid-token')
            ->postJson('/api/voice/tools/identify-patient', [
                'phone_e164' => '+593000000001',
            ])
            ->assertStatus(401);
    }
}
