<?php

namespace Tests\Feature\Http;

use App\Enums\WhatsappMessageStatus;
use App\Models\Professional;
use App\Models\Procedure;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_verify_returns_challenge(): void
    {
        $response = $this->getJson('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=dental-commissions-verify&hub.challenge=CHALLENGE_ACCEPTED');

        $response->assertOk();
        $response->assertJson(['challenge' => 'CHALLENGE_ACCEPTED']);
    }

    public function test_webhook_verify_rejects_invalid_token(): void
    {
        $response = $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=wrong-token&hub.challenge=test');

        $response->assertStatus(403);
    }

    public function test_webhook_receive_processes_message(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '+573001112233',
                                        'id' => 'wamid.test.123',
                                        'timestamp' => now()->timestamp,
                                        'type' => 'text',
                                        'text' => ['body' => 'Test message from webhook'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('whatsapp_messages', [
            'from_phone' => '+573001112233',
            'message_body' => 'Test message from webhook',
        ]);
    }

    public function test_webhook_receive_ignores_non_whatsapp_objects(): void
    {
        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'ignored']);

        $this->assertEquals(0, WhatsappMessage::count());
    }

    public function test_webhook_receive_handles_multiple_messages(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '+573001112233',
                                        'id' => 'wamid.multi.1',
                                        'timestamp' => now()->timestamp,
                                        'type' => 'text',
                                        'text' => ['body' => 'First message'],
                                    ],
                                    [
                                        'from' => '+573001112233',
                                        'id' => 'wamid.multi.2',
                                        'timestamp' => now()->timestamp,
                                        'type' => 'text',
                                        'text' => ['body' => 'Second message'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);

        $response->assertOk();

        $this->assertEquals(2, WhatsappMessage::where('from_phone', '+573001112233')->count());
    }

    public function test_test_route_works_in_testing_environment(): void
    {
        Professional::factory()->create([
            'whatsapp_phone' => '+573001112233',
            'role' => 'doctor',
            'is_active' => true,
            'can_register_via_whatsapp' => true,
        ]);

        Procedure::create([
            'name' => 'Limpieza dental',
            'code' => 'OD001',
            'description' => 'Limpieza dental completa',
            'internal_rate' => 50.00,
            'is_active' => true,
        ]);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode([
                                'patient_name' => 'Juan Perez',
                                'procedures' => ['Limpieza dental'],
                                'assistants' => [],
                                'date' => now()->format('Y-m-d'),
                                'needs_review' => false,
                                'review_notes' => '',
                            ]),
                        ],
                        'logprobs' => null,
                        'finish_reason' => 'stop',
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/test/whatsapp', [
            'phone' => '+573001112233',
            'message' => 'Limpieza para Juan Perez',
        ]);

        if ($response->status() === 500) {
            $this->fail('500 error: ' . json_encode($response->json()));
        }

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'message_id',
            'professional',
            'whatsapp_status',
        ]);
    }

    public function test_test_route_validates_required_fields(): void
    {
        $response = $this->postJson('/test/whatsapp', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone', 'message']);
    }
}
