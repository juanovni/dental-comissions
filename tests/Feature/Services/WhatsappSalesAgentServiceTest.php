<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Events\ClosingOpportunityDetected;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\AppointmentAvailabilityService;
use App\Services\WhatsappSalesAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
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
    }

    public function test_ready_to_book_message_creates_closing_opportunity_alert(): void
    {
        Event::fake();

        $procedure = Procedure::factory()->create(['name' => 'Implantes dentales']);
        $comment = $this->socialComment($procedure);
        $message = $this->whatsappMessage('Quiero agendar una cita');

        config(['services.ai.provider' => 'local']);

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('fallback', $response['source']);
        $this->assertTrue($response['requires_human_handoff']);

        Event::assertDispatched(ClosingOpportunityDetected::class);
    }

    public function test_local_language_pattern_detects_ready_to_book_message(): void
    {
        config(['services.ai.provider' => 'local']);

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $comment = $this->socialComment($procedure);
        $message = $this->whatsappMessage('Tiene chance este jueves en la tardecita?');

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('appointment_interest', $response['intent']);
        $this->assertTrue($response['appointment_candidate']['wants_appointment']);
        $this->assertSame('afternoon', $response['appointment_candidate']['preferred_period']);
    }

    public function test_ai_response_is_validated_and_persisted(): void
    {
        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);
        $comment = $this->socialComment($procedure);
        $message = $this->whatsappMessage('Quiero una cita');

        Http::fake(fn () => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode([
                                'reply' => 'Hola Maria, claro que podemos ayudarte con la limpieza dental. ¿Te gustaria agendar una valoracion?',
                                'intent' => 'appointment_interest',
                                'closing_opportunity_score' => 82,
                                'requires_human_handoff' => false,
                                'handoff_reason' => '',
                                'suggested_pipeline_stage' => 'appointment',
                                'clinical_safety_flag' => false,
                                'appointment_candidate' => [
                                    'wants_appointment' => false,
                                    'preferred_date_text' => null,
                                    'preferred_time_text' => null,
                                ],
                            ])],
                        ],
                    ],
                ],
            ],
        ]));

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('ai', $response['source']);
        $this->assertSame('appointment_interest', $response['intent']);
        $this->assertSame(82, $response['closing_opportunity_score']);
        $this->assertSame($response, $message->refresh()->ai_response);
    }

    public function test_respond_with_suggested_doctor_uses_google_calendar_slots(): void
    {
        config(['services.ai.provider' => 'local']);
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_propose_slots'],
            ['value' => true, 'value_type' => 'boolean', 'label' => 'Proponer slots'],
        );

        $procedure = Procedure::factory()->create(['name' => 'Limpieza dental']);

        $token = [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Carlos Ramirez',
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $comment = $this->socialComment($procedure, $doctor);
        $message = $this->whatsappMessage('Quiero agendar una cita');

        $mockSlots = [now()->addDay()->setTime(10, 0), now()->addDay()->setTime(11, 0)];

        $mockService = $this->createMock(AppointmentAvailabilityService::class);
        $mockService->expects($this->once())
            ->method('nextAvailableSlotsForDoctor')
            ->with($this->callback(fn($p) => $p->id === $doctor->id))
            ->willReturn($mockSlots);

        $this->app->instance(AppointmentAvailabilityService::class, $mockService);

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('fallback', $response['source']);
        $this->assertStringContainsString('Limpieza dental', $response['reply']);
    }

    public function test_respond_with_suggested_doctor_without_calendar_uses_generic_slots(): void
    {
        config(['services.ai.provider' => 'local']);
        SocialCrmSetting::updateOrCreate(
            ['key' => 'social_appointment_propose_slots'],
            ['value' => true, 'value_type' => 'boolean', 'label' => 'Proponer slots'],
        );

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia']);

        $doctor = Professional::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Juan Perez',
            'google_calendar_enabled' => false,
        ]);

        $comment = $this->socialComment($procedure, $doctor);
        $message = $this->whatsappMessage('Quiero agendar una cita');

        $mockService = $this->createMock(AppointmentAvailabilityService::class);
        $mockService->expects($this->once())
            ->method('nextAvailableSlots')
            ->willReturn([]);

        $this->app->instance(AppointmentAvailabilityService::class, $mockService);

        $response = app(WhatsappSalesAgentService::class)->respond($comment, $message);

        $this->assertSame('fallback', $response['source']);
    }

    private function socialComment(Procedure $procedure, ?Professional $doctor = null): SocialComment
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
            'suggested_doctor_id' => $doctor?->id,
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
