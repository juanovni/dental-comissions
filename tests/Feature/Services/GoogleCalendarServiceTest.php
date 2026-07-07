<?php

namespace Tests\Feature\Services;

use App\Models\Professional;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as CalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Oauth2 as GoogleOAuth2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GoogleCalendarService::class);

        config([
            'services.google_oauth' => [
                'client_id' => 'test-client-id.apps.googleusercontent.com',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'https://example.com/admin/integrations/google-calendar/callback',
                'scopes' => [
                    'https://www.googleapis.com/auth/calendar.readonly',
                    'https://www.googleapis.com/auth/userinfo.email',
                ],
                'access_type' => 'offline',
                'prompt' => 'consent',
            ],
        ]);
    }

    public function test_client_returns_configured_google_client(): void
    {
        $client = $this->service->client();

        $this->assertInstanceOf(GoogleClient::class, $client);
        $this->assertSame('test-client-id.apps.googleusercontent.com', $client->getClientId());
        $this->assertSame('test-client-secret', $client->getClientSecret());
        $this->assertSame('https://example.com/admin/integrations/google-calendar/callback', $client->getRedirectUri());
        $this->assertSame('offline', $client->getConfig('access_type'));
    }

    public function test_client_for_professional_returns_null_when_not_configured(): void
    {
        $professional = Professional::factory()->create([
            'google_calendar_enabled' => false,
            'google_calendar_token' => null,
        ]);

        $client = $this->service->clientForProfessional($professional);

        $this->assertNull($client);
    }

    public function test_client_for_professional_returns_client_when_configured(): void
    {
        $token = [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $client = $this->service->clientForProfessional($professional);

        $this->assertInstanceOf(GoogleClient::class, $client);
        $this->assertSame('test-access-token', $client->getAccessToken()['access_token']);
    }

    public function test_exchange_code_stores_token_and_enables_calendar(): void
    {
        $token = [
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => false,
            'google_calendar_token' => null,
            'google_calendar_email' => null,
        ]);

        $mockClient = $this->createMock(GoogleClient::class);
        $mockClient->method('fetchAccessTokenWithAuthCode')
            ->with('test-auth-code')
            ->willReturn($token);

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['client', 'getUserEmail'])
            ->getMock();

        $service->method('client')
            ->willReturn($mockClient);

        $service->method('getUserEmail')
            ->willReturn('doctor@example.com');

        $result = $service->exchangeCode($professional, 'test-auth-code');

        $this->assertTrue($result);

        $professional->refresh();
        $this->assertTrue($professional->google_calendar_enabled);
        $this->assertSame('doctor@example.com', $professional->google_calendar_email);

        $storedToken = $professional->getGoogleCalendarTokenDecrypted();
        $this->assertSame('new-access-token', $storedToken['access_token']);
        $this->assertSame('new-refresh-token', $storedToken['refresh_token']);
    }

    public function test_exchange_code_returns_false_on_error(): void
    {
        $professional = Professional::factory()->create();

        $mockClient = $this->createMock(GoogleClient::class);
        $mockClient->method('fetchAccessTokenWithAuthCode')
            ->with('invalid-code')
            ->willReturn(['error' => 'invalid_grant', 'error_description' => 'Code expired']);

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['client'])
            ->getMock();

        $service->method('client')
            ->willReturn($mockClient);

        $result = $service->exchangeCode($professional, 'invalid-code');

        $this->assertFalse($result);
        $professional->refresh();
        $this->assertFalse($professional->google_calendar_enabled);
    }

    public function test_list_events_returns_empty_array_when_no_professional_config(): void
    {
        $professional = Professional::factory()->create([
            'google_calendar_enabled' => false,
        ]);

        $events = $this->service->listEvents(
            $professional,
            Carbon::parse('2026-07-01 00:00:00'),
            Carbon::parse('2026-07-01 23:59:59'),
        );

        $this->assertIsArray($events);
        $this->assertEmpty($events);
    }

    public function test_is_slot_available_returns_true_for_free_slot(): void
    {
        $token = [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $eventStart = new EventDateTime();
        $eventStart->setDateTime('2026-07-01T10:00:00-05:00');
        $eventEnd = new EventDateTime();
        $eventEnd->setDateTime('2026-07-01T11:00:00-05:00');

        $event = new CalendarEvent();
        $event->setStart($eventStart);
        $event->setEnd($eventEnd);

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['listEvents'])
            ->getMock();

        $service->method('listEvents')
            ->willReturn([$event]);

        $available = $service->isSlotAvailable(
            $professional,
            Carbon::parse('2026-07-01 11:00:00', 'America/Chicago'),
            Carbon::parse('2026-07-01 12:00:00', 'America/Chicago'),
        );

        $this->assertTrue($available);
    }

    public function test_is_slot_available_returns_false_for_overlapping_slot(): void
    {
        $token = [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $eventStart = new EventDateTime();
        $eventStart->setDateTime('2026-07-01T10:00:00-05:00');
        $eventEnd = new EventDateTime();
        $eventEnd->setDateTime('2026-07-01T11:00:00-05:00');

        $event = new CalendarEvent();
        $event->setStart($eventStart);
        $event->setEnd($eventEnd);

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['listEvents'])
            ->getMock();

        $service->method('listEvents')
            ->willReturn([$event]);

        $available = $service->isSlotAvailable(
            $professional,
            Carbon::parse('2026-07-01 10:30:00', 'America/Chicago'),
            Carbon::parse('2026-07-01 11:30:00', 'America/Chicago'),
        );

        $this->assertFalse($available);
    }

    public function test_available_slots_generates_slots_correctly(): void
    {
        $token = [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $eventStart = new EventDateTime();
        $eventStart->setDateTime('2026-07-01T10:00:00-05:00');
        $eventEnd = new EventDateTime();
        $eventEnd->setDateTime('2026-07-01T11:00:00-05:00');

        $event = new CalendarEvent();
        $event->setStart($eventStart);
        $event->setEnd($eventEnd);

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['listEvents'])
            ->getMock();

        $service->method('listEvents')
            ->willReturn([$event]);

        $date = Carbon::parse('2026-07-01', 'America/Chicago');
        $dayStart = $date->copy()->setTime(8, 0);
        $dayEnd = $date->copy()->setTime(18, 0);

        $slots = $service->availableSlots(
            $professional,
            $date,
            60,
            $dayStart,
            $dayEnd,
        );

        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            $this->assertArrayHasKey('start', $slot);
            $this->assertArrayHasKey('end', $slot);
            $this->assertInstanceOf(Carbon::class, $slot['start']);
            $this->assertInstanceOf(Carbon::class, $slot['end']);
        }

        $slotTimes = array_map(fn($s) => $s['start']->format('H:i'), $slots);
        $this->assertContains('08:00', $slotTimes);
        $this->assertNotContains('10:00', $slotTimes);
        $this->assertContains('11:00', $slotTimes);
    }

    public function test_revoke_token_disconnects_professional(): void
    {
        $token = [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
            'google_calendar_email' => 'doctor@example.com',
        ]);

        $mockClient = $this->createMock(GoogleClient::class);
        $mockClient->method('isAccessTokenExpired')
            ->willReturn(false);
        $mockClient->expects($this->once())
            ->method('revokeToken')
            ->with('test-access-token');

        $service = $this->getMockBuilder(GoogleCalendarService::class)
            ->onlyMethods(['clientForProfessional'])
            ->getMock();

        $service->method('clientForProfessional')
            ->willReturn($mockClient);

        $result = $service->revokeToken($professional);

        $this->assertTrue($result);

        $professional->refresh();
        $this->assertFalse($professional->google_calendar_enabled);
        $this->assertNull($professional->google_calendar_token);
        $this->assertNull($professional->google_calendar_email);
    }
}
