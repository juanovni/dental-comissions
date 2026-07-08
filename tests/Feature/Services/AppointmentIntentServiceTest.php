<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Services\AppointmentIntentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentIntentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentIntentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppointmentIntentService::class);
    }

    public function test_extract_manana_date(): void
    {
        $result = $this->service->extractFromText('Quiero agendar para mañana');

        $expected = Carbon::now()->addDay()->format('Y-m-d');
        $this->assertSame($expected, $result['date']);
    }

    public function test_extract_hoy_date(): void
    {
        $result = $this->service->extractFromText('Puedo hoy a las 3pm');

        $expected = Carbon::now()->format('Y-m-d');
        $this->assertSame($expected, $result['date']);
    }

    public function test_extract_pasado_manana_date(): void
    {
        $result = $this->service->extractFromText('Pasado mañana en la tarde');

        $expected = Carbon::now()->addDays(2)->format('Y-m-d');
        $this->assertSame($expected, $result['date']);
    }

    public function test_extract_day_of_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07')); // Tuesday

        $result = $this->service->extractFromText('El viernes en la mañana');

        $this->assertSame('2026-07-10', $result['date']);
        $this->assertSame('09:00', $result['time']);

        Carbon::setTestNow();
    }

    public function test_extract_next_week_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07')); // Tuesday

        $result = $this->service->extractFromText('El próximo lunes');

        $this->assertSame('2026-07-13', $result['date']);

        Carbon::setTestNow();
    }

    public function test_extract_date_with_month_name(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07'));

        $result = $this->service->extractFromText('15 de agosto');

        $this->assertSame('2026-08-15', $result['date']);

        Carbon::setTestNow();
    }

    public function test_extract_date_with_slash_format(): void
    {
        $result = $this->service->extractFromText('Puedo el 20/08');

        $this->assertSame('2026-08-20', $result['date']);
    }

    public function test_extract_time_with_format(): void
    {
        $result = $this->service->extractFromText('a las 3:30pm');

        $this->assertSame('15:30', $result['time']);
    }

    public function test_extract_time_24h(): void
    {
        $result = $this->service->extractFromText('a las 15:30');

        $this->assertSame('15:30', $result['time']);
    }

    public function test_extract_time_period_manana(): void
    {
        $result = $this->service->extractFromText('en la mañana');

        $this->assertSame('09:00', $result['time']);
    }

    public function test_extract_time_period_tarde(): void
    {
        $result = $this->service->extractFromText('por la tarde');

        $this->assertSame('15:00', $result['time']);
    }

    public function test_analyze_with_appointment_intent_and_date(): void
    {
        $comment = $this->createComment();
        $message = $this->createMessage('Quiero agendar para el viernes a las 10am');

        Carbon::setTestNow(Carbon::parse('2026-07-07')); // Tuesday

        $result = $this->service->analyze($comment, $message, 'appointment_interest', [
            'wants_appointment' => true,
            'preferred_date_text' => null,
            'preferred_time_text' => null,
        ]);

        $this->assertTrue($result['has_intent']);
        $this->assertSame('appointment_interest', $result['intent_type']);
        $this->assertSame('2026-07-10', $result['preferred_date_parsed']);
        $this->assertSame('10:00', $result['preferred_time_parsed']);

        $comment->refresh();
        $this->assertNotNull($comment->appointment_scheduled_at);

        $action = $comment->actions()->latest()->first();
        $this->assertSame(SocialCommentActionType::BookingIntentDetected, $action->action);

        Carbon::setTestNow();
    }

    public function test_analyze_does_not_detect_when_no_intent(): void
    {
        $comment = $this->createComment();
        $message = $this->createMessage('Gracias por la informacion');

        $result = $this->service->analyze($comment, $message, 'information_seeking', [
            'wants_appointment' => false,
            'preferred_date_text' => null,
            'preferred_time_text' => null,
        ]);

        $this->assertFalse($result['has_intent']);
    }

    public function test_analyze_with_ai_date_text(): void
    {
        $comment = $this->createComment();
        $message = $this->createMessage('Quiero agendar');

        $result = $this->service->analyze($comment, $message, 'appointment_interest', [
            'wants_appointment' => true,
            'preferred_date_text' => 'mañana',
            'preferred_time_text' => 'a las 2pm',
        ]);

        $this->assertTrue($result['has_intent']);
        $this->assertNotNull($result['preferred_date_parsed']);
        $this->assertSame('14:00', $result['preferred_time_parsed']);
        $this->assertSame('ai', $result['extraction_source']);
    }

    public function test_ready_to_book_intent(): void
    {
        $comment = $this->createComment();
        $message = $this->createMessage('Si, quiero agendar ya');

        $result = $this->service->analyze($comment, $message, 'ready_to_book', [
            'wants_appointment' => true,
            'preferred_date_text' => null,
            'preferred_time_text' => null,
        ]);

        $this->assertTrue($result['has_intent']);
        $this->assertSame('ready_to_book', $result['intent_type']);
    }

    public function test_analyze_conversation_history(): void
    {
        $messages = [
            ['role' => 'assistant', 'content' => 'Hola, en que puedo ayudarte?'],
            ['role' => 'user', 'content' => 'Quiero agendar para el lunes en la mañana'],
            ['role' => 'assistant', 'content' => 'Claro, te muestro horarios'],
            ['role' => 'user', 'content' => 'A las 10am esta bien'],
        ];

        Carbon::setTestNow(Carbon::parse('2026-07-07')); // Tuesday

        $result = $this->service->analyzeConversationHistory($messages);

        $this->assertNotNull($result['date']);
        $this->assertSame('10:00', $result['time']);

        Carbon::setTestNow();
    }

    public function test_extract_proxima_semana(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07')); // Tuesday

        $result = $this->service->extractFromText('La proxima semana');

        $expected = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $this->assertSame($expected, $result['date']);

        Carbon::setTestNow();
    }

    private function createComment(): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Test',
            'external_account_id' => 'test_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Test',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'test_user',
            'display_name' => 'Test User',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_'.uniqid(),
            'author_name' => 'Test User',
            'author_username' => 'test_user',
            'comment_text' => 'Test',
        ]);
    }

    private function createMessage(string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => '593985925100',
            'to_phone' => 'test-phone',
            'message_body' => $body,
            'message_sid' => 'wamid.'.uniqid(),
        ]);
    }
}
