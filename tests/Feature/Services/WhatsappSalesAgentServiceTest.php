<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Procedure;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\WhatsappSalesAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappSalesAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fallback_response_uses_lead_context_without_clinical_diagnosis(): void
    {
        config(['services.ai.provider' => 'local']);

        $procedure = Procedure::factory()->create(['name' => 'Implantes dentales']);
        $comment = $this->socialComment($procedure);
        $message = $this->whatsappMessage('Hola, mi codigo es DNT-ABCDE');

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('fallback', $response['source']);
        $this->assertStringContainsString('Implantes dentales', $response['reply']);
        $this->assertStringContainsString('valoracion', $response['reply']);
        $this->assertStringNotContainsString('En que puedo ayudarte', $response['reply']);
        $this->assertSame('general_interest', $response['intent']);
        $this->assertFalse($response['requires_human_handoff']);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::WhatsappSalesAgent->value,
        ]);
    }

    public function test_ready_to_book_message_creates_closing_opportunity_alert(): void
    {
        config(['services.ai.provider' => 'local']);

        $comment = $this->socialComment(Procedure::factory()->create(['name' => 'Ortodoncia']));
        $message = $this->whatsappMessage('Quiero agendar una cita manana en la tarde. Mi codigo es DNT-ABCDE');

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('ready_to_book', $response['intent']);
        $this->assertTrue($response['requires_human_handoff']);
        $this->assertGreaterThanOrEqual(75, $response['closing_opportunity_score']);
        $this->assertDatabaseHas('social_lead_alerts', [
            'social_comment_id' => $comment->id,
            'alert_type' => 'closing_opportunity',
            'severity' => 'danger',
        ]);
    }

    public function test_ai_response_is_validated_and_persisted(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-key',
        ]);

        Http::fake(fn () => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'reply' => 'Hola Maria. Vi que te intereso el video de implantes dentales. Te ayudo a coordinar una valoracion con el equipo.',
                            'intent' => 'appointment_interest',
                            'closing_opportunity_score' => 82,
                            'requires_human_handoff' => true,
                            'handoff_reason' => 'Quiere revisar disponibilidad.',
                            'suggested_pipeline_stage' => 'appointment',
                            'clinical_safety_flag' => false,
                            'appointment_candidate' => [
                                'wants_appointment' => true,
                                'preferred_date_text' => 'manana',
                                'preferred_time_text' => 'tarde',
                            ],
                        ]),
                    ]],
                ],
            ]],
        ]));

        $comment = $this->socialComment(Procedure::factory()->create(['name' => 'Implantes dentales']));
        $message = $this->whatsappMessage('Quiero una cita');

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('ai', $response['source']);
        $this->assertSame('appointment_interest', $response['intent']);
        $this->assertSame(82, $response['closing_opportunity_score']);
        $this->assertSame($response, $message->refresh()->ai_response);
    }

    private function socialComment(Procedure $procedure): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Video de '.$procedure->name,
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'maria_test',
            'display_name' => 'Maria Perez',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'suggested_procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Maria Perez',
            'author_username' => 'maria_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero informacion',
            'tracking_token' => 'DNT-ABCDE',
        ]);
    }

    private function whatsappMessage(string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => '593985925100',
            'to_phone' => 'test-phone-number-id',
            'message_body' => $body,
            'message_sid' => 'wamid.'.uniqid(),
        ]);
    }
}
