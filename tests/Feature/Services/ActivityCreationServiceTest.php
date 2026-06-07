<?php

namespace Tests\Feature\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityRecord;
use App\Models\DoctorAssistantAssignment;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodCommissionRate;
use App\Models\Procedure;
use App\Models\Professional;
use App\Services\ActivityCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityCreationService $creationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creationService = app(ActivityCreationService::class);
        $this->seedPaymentMethods();
    }

    public function test_create_activity_from_parsed_data(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);
        $assistant = Professional::factory()->create(['role' => 'assistant']);

        DoctorAssistantAssignment::create([
            'doctor_id' => $doctor->id,
            'assistant_id' => $assistant->id,
            'is_active' => true,
        ]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'test_msg_001',
        ]);

        $parsedData = [
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => [$assistant->name],
            'payment_method' => 'efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertInstanceOf(ActivityRecord::class, $activity);
        $this->assertEquals($doctor->id, $activity->doctor_id);
        $this->assertEquals($procedure->id, $activity->procedure_id);
        $this->assertEquals(ActivityStatus::PendingConfirmation, $activity->status);
    }

    public function test_create_activity_auto_creates_patient(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create(['name' => 'Resina simple', 'is_active' => true]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Resina para Maria Garcia',
            'message_sid' => 'test_msg_002',
        ]);

        $this->assertEquals(0, Patient::where('full_name', 'Maria Garcia')->count());

        $parsedData = [
            'patient_name' => 'Maria Garcia',
            'procedures' => ['Resina simple'],
            'assistants' => [],
            'payment_method' => 'transferencia',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertEquals(1, Patient::where('full_name', 'Maria Garcia')->count());
    }

    public function test_create_activity_matches_procedure(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create(['name' => 'Extraccion simple', 'is_active' => true]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Extraccion para Pedro Luis',
            'message_sid' => 'test_msg_003',
        ]);

        $parsedData = [
            'patient_name' => 'Pedro Luis',
            'procedures' => ['Extraccion simple'],
            'assistants' => [],
            'payment_method' => 'efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertEquals($procedure->id, $activity->procedure_id);
    }

    public function test_create_activity_validates_assistants(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);
        $assignedAssistant = Professional::factory()->create(['role' => 'assistant', 'name' => 'Ana Garcia']);
        $unassignedAssistant = Professional::factory()->create(['role' => 'assistant', 'name' => 'Otro Auxiliar']);

        DoctorAssistantAssignment::create([
            'doctor_id' => $doctor->id,
            'assistant_id' => $assignedAssistant->id,
            'is_active' => true,
        ]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza con Ana y Otro Auxiliar',
            'message_sid' => 'test_msg_004',
        ]);

        $parsedData = [
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => ['Ana Garcia', 'Otro Auxiliar'],
            'payment_method' => 'efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertCount(1, $activity->assistants);
        $this->assertEquals($assignedAssistant->id, $activity->assistants->first()->id);
    }

    public function test_create_activity_calculates_commissions(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create([
            'name' => 'Limpieza dental',
            'is_active' => true,
            'internal_rate' => 50.00,
        ]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'test_msg_005',
        ]);

        $parsedData = [
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => [],
            'payment_method' => 'tc',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertEquals(1.06, (float) $activity->doctor_commission_amount);
        $this->assertEquals(1.06, (float) $activity->payment_method_commission_snapshot);
    }

    public function test_create_activity_handles_unknown_procedure(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Procedimiento fantasma',
            'message_sid' => 'test_msg_006',
        ]);

        $parsedData = [
            'patient_name' => 'Juan Perez',
            'procedures' => ['Procedimiento inexistente'],
            'assistants' => [],
            'payment_method' => 'efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNull($activity);
        $this->assertEquals('needs_review', $whatsappMessage->fresh()->status->value);
    }

    public function test_create_activity_marks_needs_review_when_flagged(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para alguien',
            'message_sid' => 'test_msg_007',
        ]);

        $parsedData = [
            'patient_name' => 'Alguien',
            'procedures' => ['Limpieza dental'],
            'assistants' => [],
            'payment_method' => 'debito',
            'date' => now()->format('Y-m-d'),
            'needs_review' => true,
            'review_notes' => 'Nombre del paciente no claro',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertEquals(ActivityStatus::NeedsReview, $activity->status);
        $this->assertEquals('Nombre del paciente no claro', $activity->correction_notes);
    }

    public function test_create_activity_rejects_missing_payment_method(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'test_msg_008',
        ]);

        $parsedData = [
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => [],
            'payment_method' => '',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ];

        $activity = $this->creationService->create($parsedData, $doctor, $whatsappMessage);

        $this->assertNull($activity);
        $this->assertEquals('failed', $whatsappMessage->fresh()->status->value);
        $this->assertEquals('Falta metodo de pago', $whatsappMessage->fresh()->error_message);
        $this->assertEquals(0, Patient::where('full_name', 'Juan Perez')->count());
    }

    public function test_assistants_are_saved_without_commission(): void
    {
        $doctor = Professional::factory()->create(['role' => 'doctor']);
        Procedure::factory()->create(['name' => 'Limpieza dental', 'is_active' => true]);
        $assistant = Professional::factory()->create(['role' => 'assistant', 'name' => 'Ana Garcia']);

        DoctorAssistantAssignment::create([
            'doctor_id' => $doctor->id,
            'assistant_id' => $assistant->id,
            'is_active' => true,
        ]);

        $whatsappMessage = \App\Models\WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => 'received',
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez con Ana efectivo',
            'message_sid' => 'test_msg_009',
        ]);

        $activity = $this->creationService->create([
            'patient_name' => 'Juan Perez',
            'procedures' => ['Limpieza dental'],
            'assistants' => ['Ana Garcia'],
            'payment_method' => 'efectivo',
            'date' => now()->format('Y-m-d'),
            'needs_review' => false,
            'review_notes' => '',
        ], $doctor, $whatsappMessage);

        $this->assertNotNull($activity);
        $this->assertEquals(0.00, (float) $activity->assistant_commission_total);
        $this->assertEquals(0.00, (float) $activity->assistants()->first()->pivot->commission_amount);
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            'EFECTIVO' => ['name' => 'Efectivo', 'aliases' => ['efectivo', 'efe'], 'amount' => 1.25],
            'TRANSFERENCIA' => ['name' => 'Transferencia', 'aliases' => ['transferencia', 'transf'], 'amount' => 1.25],
            'CREDITO' => ['name' => 'Credito', 'aliases' => ['credito', 'tc'], 'amount' => 1.06],
            'DEBITO' => ['name' => 'Debito', 'aliases' => ['debito', 'td'], 'amount' => 1.19],
        ];

        foreach ($methods as $code => $data) {
            $paymentMethod = PaymentMethod::create([
                'name' => $data['name'],
                'code' => $code,
                'aliases' => $data['aliases'],
                'is_active' => true,
            ]);

            PaymentMethodCommissionRate::create([
                'payment_method_id' => $paymentMethod->id,
                'amount' => $data['amount'],
                'is_active' => true,
            ]);
        }
    }
}
