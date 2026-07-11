<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\CalendarIntegration;
use App\Models\Patient;
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
                    'https://www.googleapis.com/auth/calendar.events',
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

    public function test_build_calendar_event_sets_summary_and_times(): void
    {
        $patient = Patient::factory()->create(['full_name' => 'Juan Perez']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
            'duration_minutes' => 60,
        ]);

        $event = $this->service->buildCalendarEvent($appointment);

        $this->assertStringContainsString('Juan Perez', $event->getSummary());
        $this->assertSame(
            $appointment->scheduled_at->toRfc3339String(),
            $event->getStart()->getDateTime(),
        );

        $expectedEnd = $appointment->scheduled_at->copy()->addMinutes(60);
        $this->assertSame(
            $expectedEnd->toRfc3339String(),
            $event->getEnd()->getDateTime(),
        );
    }

    public function test_create_or_update_event_creates_new_when_no_external_id(): void
    {
        $appointment = Appointment::factory()->withDoctorWithCalendar()->create([
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
            'external_appointment_id' => null,
        ]);

        $createdEvent = new CalendarEvent();
        $createdEvent->setId('test-event-id-123');

        $this->connectClinicCalendar();
        $calendarId = 'primary';

        $eventsResource = $this->createMock(\Google\Service\Calendar\Resource\Events::class);
        $eventsResource->method('insert')
            ->with($calendarId, $this->isInstanceOf(CalendarEvent::class))
            ->willReturn($createdEvent);

        $service = $this->getMockBuilder($this->service::class)
            ->onlyMethods(['clientForClinic', 'makeCalendarService'])
            ->getMock();

        $service->method('clientForClinic')
            ->willReturn($this->createMock(GoogleClient::class));

        $service->method('makeCalendarService')
            ->willReturnCallback(function ($client) use ($eventsResource) {
                $calendar = new GoogleCalendar($client);
                $calendar->events = $eventsResource;
                return $calendar;
            });

        $result = $this->callPrivateMethod($service, 'createOrUpdateEvent', $appointment);

        $this->assertSame('test-event-id-123', $result);

        $appointment->refresh();
        $this->assertSame('test-event-id-123', $appointment->external_appointment_id);
        $this->assertSame('active', $appointment->external_status);
        $this->assertNotNull($appointment->last_synced_at);
        $this->assertNull($appointment->sync_error);
    }

    public function test_create_or_update_event_updates_when_external_id_exists(): void
    {
        $appointment = Appointment::factory()->withDoctorWithCalendar()->create([
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
            'external_appointment_id' => 'existing-event-id',
            'external_provider' => 'google_calendar',
        ]);

        $updatedEvent = new CalendarEvent();
        $updatedEvent->setId('existing-event-id');

        $this->connectClinicCalendar();
        $calendarId = 'primary';

        $eventsResource = $this->createMock(\Google\Service\Calendar\Resource\Events::class);
        $eventsResource->method('update')
            ->with($calendarId, 'existing-event-id', $this->isInstanceOf(CalendarEvent::class))
            ->willReturn($updatedEvent);

        $service = $this->getMockBuilder($this->service::class)
            ->onlyMethods(['clientForClinic', 'makeCalendarService'])
            ->getMock();

        $service->method('clientForClinic')
            ->willReturn($this->createMock(GoogleClient::class));

        $service->method('makeCalendarService')
            ->willReturnCallback(function ($client) use ($eventsResource) {
                $calendar = new GoogleCalendar($client);
                $calendar->events = $eventsResource;
                return $calendar;
            });

        $result = $this->callPrivateMethod($service, 'createOrUpdateEvent', $appointment);

        $this->assertSame('existing-event-id', $result);
        $appointment->refresh();
        $this->assertSame('existing-event-id', $appointment->external_appointment_id);
    }

    public function test_delete_event_removes_from_calendar_and_clears_fields(): void
    {
        $appointment = Appointment::factory()->withDoctorWithCalendar()->create([
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
            'external_appointment_id' => 'event-to-delete',
        ]);

        $this->connectClinicCalendar();
        $calendarId = 'primary';

        $eventsResource = $this->createMock(\Google\Service\Calendar\Resource\Events::class);
        $eventsResource->method('delete')
            ->with($calendarId, 'event-to-delete');

        $service = $this->getMockBuilder($this->service::class)
            ->onlyMethods(['clientForClinic', 'makeCalendarService'])
            ->getMock();

        $service->method('clientForClinic')
            ->willReturn($this->createMock(GoogleClient::class));

        $service->method('makeCalendarService')
            ->willReturnCallback(function ($client) use ($eventsResource) {
                $calendar = new GoogleCalendar($client);
                $calendar->events = $eventsResource;
                return $calendar;
            });

        $result = $this->callPrivateMethod($service, 'deleteEvent', $appointment);

        $this->assertTrue($result);
        $appointment->refresh();
        $this->assertNull($appointment->external_appointment_id);
        $this->assertSame('deleted', $appointment->external_status);
    }

    public function test_delete_event_noop_when_no_external_id(): void
    {
        $appointment = Appointment::factory()->create([
            'external_appointment_id' => null,
        ]);

        $result = $this->service->deleteEvent($appointment);

        $this->assertTrue($result);
    }

    public function test_sync_appointment_creates_event_for_scheduled(): void
    {
        $createdEvent = new CalendarEvent();
        $createdEvent->setId('test-event-sync');

        $appointment = Appointment::factory()->withDoctorWithCalendar()->create([
            'scheduled_at' => now()->addDay()->setHour(10)->setMinute(0),
            'status' => AppointmentStatus::Scheduled,
            'external_appointment_id' => null,
        ]);

        $this->connectClinicCalendar();
        $calendarId = 'primary';

        $eventsResource = $this->createMock(\Google\Service\Calendar\Resource\Events::class);
        $eventsResource->method('insert')
            ->with($calendarId, $this->isInstanceOf(CalendarEvent::class))
            ->willReturn($createdEvent);

        $service = $this->getMockBuilder($this->service::class)
            ->onlyMethods(['clientForClinic', 'makeCalendarService'])
            ->getMock();

        $service->method('clientForClinic')
            ->willReturn($this->createMock(GoogleClient::class));

        $service->method('makeCalendarService')
            ->willReturnCallback(function ($client) use ($eventsResource) {
                $calendar = new GoogleCalendar($client);
                $calendar->events = $eventsResource;
                return $calendar;
            });

        $result = $this->callPrivateMethod($service, 'syncAppointment', $appointment);

        $appointment->refresh();
        $this->assertNotNull($appointment->external_appointment_id);
        $this->assertNotNull($result);
    }

    public function test_sync_appointment_deletes_event_for_cancelled(): void
    {
        $appointment = Appointment::factory()->withDoctorWithCalendar()->create([
            'status' => AppointmentStatus::Cancelled,
            'external_appointment_id' => 'event-to-delete',
        ]);

        $this->connectClinicCalendar();
        $calendarId = 'primary';

        $eventsResource = $this->createMock(\Google\Service\Calendar\Resource\Events::class);
        $eventsResource->method('delete')
            ->with($calendarId, 'event-to-delete');

        $service = $this->getMockBuilder($this->service::class)
            ->onlyMethods(['clientForClinic', 'makeCalendarService'])
            ->getMock();

        $service->method('clientForClinic')
            ->willReturn($this->createMock(GoogleClient::class));

        $service->method('makeCalendarService')
            ->willReturnCallback(function ($client) use ($eventsResource) {
                $calendar = new GoogleCalendar($client);
                $calendar->events = $eventsResource;
                return $calendar;
            });

        $result = $this->callPrivateMethod($service, 'syncAppointment', $appointment);

        $this->assertNull($result);
        $appointment->refresh();
        $this->assertNull($appointment->external_appointment_id);
        $this->assertSame('deleted', $appointment->external_status);
    }

    private function callPrivateMethod(object $object, string $method, ...$args): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($object, ...$args);
    }

    private function connectClinicCalendar(): void
    {
        CalendarIntegration::clinicGoogle()->update([
            'account_email' => 'clinic@example.com',
            'calendar_id' => 'primary',
            'token' => Crypt::encryptString(json_encode([
                'access_token' => 'clinic-token',
                'refresh_token' => 'clinic-refresh',
                'expires_in' => 3600,
                'created' => now()->timestamp,
            ])),
            'token_expires_at' => now()->addHour(),
            'is_enabled' => true,
        ]);
    }
}
