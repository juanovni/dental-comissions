<?php

namespace Tests\Feature\Services;

use App\Enums\WhatsappMessageStatus;
use App\Models\Professional;
use App\Models\WhatsappMessage;
use App\Services\ActivityCreationService;
use App\Services\AiParsingService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappService $whatsappService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whatsappService = app(WhatsappService::class);
        $this->fakeOpenAI();
    }

    public function test_process_incoming_message_creates_whatsapp_message(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertInstanceOf(WhatsappMessage::class, $result);
        $this->assertEquals('+573001112233', $result->from_phone);
        $this->assertEquals('Test message', $result->message_body);
    }

    public function test_process_incoming_message_identifies_professional_by_phone(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertEquals($doctor->id, $result->professional_id);
    }

    public function test_process_incoming_message_handles_unknown_phone(): void
    {
        $payload = $this->buildPayload('+573009999999', 'Test message');

        $result = $this->whatsappService->processIncomingMessage($payload);

        $this->assertNotNull($result);
        $this->assertNull($result->professional_id);
        $this->assertEquals(WhatsappMessageStatus::Failed, $result->status);
    }

    public function test_duplicate_message_is_not_processed_twice(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = $this->buildPayload('+573001112233', 'Test message');
        $result1 = $this->whatsappService->processIncomingMessage($payload);

        $result2 = $this->whatsappService->processIncomingMessage($payload);

        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals(1, WhatsappMessage::where('from_phone', '+573001112233')->count());
    }

    public function test_process_reply_ok_confirms_message(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $original = WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => WhatsappMessageStatus::Parsed,
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'original_test_123',
        ]);

        $replyPayload = $this->buildPayload('+573001112233', 'OK');
        $replyPayload['messages'][0]['context'] = ['id' => 'original_test_123'];

        $reply = $this->whatsappService->processIncomingMessage($replyPayload);

        $this->assertNotNull($reply);
        $this->assertEquals(WhatsappMessageStatus::Confirmed, $reply->status);

        $original->refresh();
        $this->assertEquals(WhatsappMessageStatus::Confirmed, $original->status);
    }

    public function test_process_reply_corregir_marks_needs_review(): void
    {
        $doctor = Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $original = WhatsappMessage::create([
            'professional_id' => $doctor->id,
            'direction' => 'incoming',
            'status' => WhatsappMessageStatus::Parsed,
            'from_phone' => '+573001112233',
            'to_phone' => '12345',
            'message_body' => 'Limpieza para Juan Perez',
            'message_sid' => 'original_test_456',
        ]);

        $replyPayload = $this->buildPayload('+573001112233', 'CORREGIR era resina');
        $replyPayload['messages'][0]['context'] = ['id' => 'original_test_456'];

        $reply = $this->whatsappService->processIncomingMessage($replyPayload);

        $this->assertNotNull($reply);
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $reply->status);
        $this->assertEquals('era resina', $reply->error_message);

        $original->refresh();
        $this->assertEquals(WhatsappMessageStatus::NeedsReview, $original->status);
    }

    private function fakeOpenAI(): void
    {
        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode([
                                'patient_name' => '',
                                'procedures' => [],
                                'assistants' => [],
                                'payment_method' => '',
                                'date' => now()->format('Y-m-d'),
                                'needs_review' => true,
                                'review_notes' => 'No se pudo procesar el mensaje',
                            ]),
                        ],
                        'logprobs' => null,
                        'finish_reason' => 'stop',
                    ],
                ],
            ]),
        ]);
    }

    private function buildPayload(string $phone, string $message): array
    {
        return [
            'messages' => [
                [
                    'from' => $phone,
                    'id' => 'test_' . uniqid(),
                    'timestamp' => now()->timestamp,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ],
            ],
        ];
    }
}
