<?php

namespace Tests\Feature\Services;

use App\Models\Professional;
use App\Models\Procedure;
use App\Models\PaymentMethod;
use App\Services\AiParsingService;
use App\Models\DoctorAssistantAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiParsingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiParsingService $aiParsingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiParsingService = app(AiParsingService::class);
        config(['services.gemini.api_key' => 'test-key']);
        PaymentMethod::create([
            'name' => 'Efectivo',
            'code' => 'EFECTIVO',
            'aliases' => ['efectivo', 'efe'],
            'is_active' => true,
        ]);
    }

    public function test_parse_message_returns_structured_data(): void
    {
        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $fakeContent = json_encode([
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => ['Ana Garcia'],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->fakeGemini($fakeContent);

        $result = $this->aiParsingService->parseMessage('Limpieza para Juan Perez con Ana Garcia', $doctor);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('patient_name', $result);
        $this->assertArrayHasKey('procedures', $result);
        $this->assertArrayHasKey('assistants', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('needs_review', $result);
    }

    public function test_parse_message_handles_missing_patient(): void
    {
        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $fakeContent = json_encode([
            'patient_name' => '',
            'procedures' => ['Limpieza dental'],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->fakeGemini($fakeContent);

        $result = $this->aiParsingService->parseMessage('Limpieza dental', $doctor);

        $this->assertTrue($result['needs_review']);
        $this->assertStringContainsString('Falta nombre del paciente', $result['review_notes']);
    }

    public function test_parse_message_handles_missing_procedure(): void
    {
        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $fakeContent = json_encode([
            'patient_name' => 'Juan Perez',
            'procedures' => [],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->fakeGemini($fakeContent);

        $result = $this->aiParsingService->parseMessage('Para Juan Perez', $doctor);

        $this->assertTrue($result['needs_review']);
        $this->assertStringContainsString('Falta procedimiento', $result['review_notes']);
    }

    public function test_parse_message_defaults_to_today_date(): void
    {
        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $fakeContent = json_encode([
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => [],
            'payment_method' => 'Efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ]);

        $this->fakeGemini($fakeContent);

        $result = $this->aiParsingService->parseMessage('Limpieza para Juan Perez', $doctor);

        $this->assertEquals(now()->format('Y-m-d'), $result['date']);
    }

    public function test_parse_message_handles_invalid_json(): void
    {
        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $this->fakeGemini('invalid json response');

        $result = $this->aiParsingService->parseMessage('Test message', $doctor);

        $this->assertTrue($result['needs_review']);
        $this->assertStringContainsString('Error al interpretar', $result['review_notes']);
    }

    public function test_parse_message_uses_local_fallback_when_gemini_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $assistant = Professional::factory()->create([
            'name' => 'Ana Garcia',
            'role' => 'assistant',
            'is_active' => true,
        ]);

        DoctorAssistantAssignment::create([
            'doctor_id' => $doctor->id,
            'assistant_id' => $assistant->id,
            'is_active' => true,
        ]);

        Procedure::factory()->create([
            'name' => 'Limpieza dental',
            'code' => 'LIMP001',
            'internal_rate' => 50.00,
            'is_active' => true,
        ]);

        $result = $this->aiParsingService->parseMessage('Limpieza dental para Juan Perez hoy con Ana Garcia efectivo', $doctor);

        $this->assertFalse($result['needs_review']);
        $this->assertSame('Juan Perez', $result['patient_name']);
        $this->assertSame(['Limpieza dental'], $result['procedures']);
        $this->assertSame(['Ana Garcia'], $result['assistants']);
        $this->assertSame('efectivo', $result['payment_method']);
        $this->assertSame(now()->format('Y-m-d'), $result['date']);
    }

    public function test_local_fallback_stops_patient_name_before_payment_text(): void
    {
        config(['services.gemini.api_key' => null]);

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        Procedure::factory()->create([
            'name' => 'Limpieza dental',
            'code' => 'LIMP001',
            'internal_rate' => 50.00,
            'is_active' => true,
        ]);

        $result = $this->aiParsingService->parseMessage(
            'Dr. Carlos Rodriguez, limpieza dental para Roberto Gomez, pago efectivo',
            $doctor,
        );

        $this->assertFalse($result['needs_review']);
        $this->assertSame('Roberto Gomez', $result['patient_name']);
        $this->assertSame(['Limpieza dental'], $result['procedures']);
        $this->assertSame('efectivo', $result['payment_method']);
    }

    public function test_local_fallback_extracts_patient_after_a_before_payment_text(): void
    {
        config(['services.gemini.api_key' => null]);

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'is_active' => true,
        ]);

        Procedure::factory()->create([
            'name' => 'Limpieza dental',
            'code' => 'LIMP001',
            'internal_rate' => 50.00,
            'is_active' => true,
        ]);

        $result = $this->aiParsingService->parseMessage(
            'Limpieza dental a Roberto Gomez pago efectivo',
            $doctor,
        );

        $this->assertFalse($result['needs_review']);
        $this->assertSame('Roberto Gomez', $result['patient_name']);
        $this->assertSame(['Limpieza dental'], $result['procedures']);
        $this->assertSame('efectivo', $result['payment_method']);
    }

    private function fakeGemini(string $content): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $content],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }
}
