<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->calendarService = $this->createMock(GoogleCalendarService::class);
    }

    private function makeWorkflow(): AppointmentWorkflowService
    {
        return new AppointmentWorkflowService($this->calendarService);
    }

    public function test_confirm_updates_status_and_syncs_to_calendar(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->calendarService->expects($this->once())
            ->method('createOrUpdateEvent')
            ->with($appointment);

        $result = $this->makeWorkflow()->confirm($appointment);

        $this->assertSame(AppointmentStatus::Confirmed, $result->status);
        $this->assertNotNull($result->confirmed_at);
    }

    public function test_reschedule_updates_date_and_syncs(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => now()->addDays(5),
            'duration_minutes' => 30,
        ]);

        $newDate = now()->addDays(10)->setHour(14)->setMinute(0);

        $this->calendarService->expects($this->once())
            ->method('createOrUpdateEvent')
            ->with($appointment);

        $result = $this->makeWorkflow()->reschedule($appointment, $newDate);

        $this->assertSame(AppointmentStatus::Rescheduled, $result->status);
        $this->assertTrue(
            $result->scheduled_at->format('Y-m-d H:i') === $newDate->format('Y-m-d H:i'),
            'Las fechas deben coincidir hasta el minuto',
        );
    }

    public function test_cancel_updates_status_and_deletes_event(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Scheduled,
            'external_appointment_id' => 'event-to-delete',
        ]);

        $this->calendarService->expects($this->once())
            ->method('deleteEvent')
            ->with($appointment);

        $result = $this->makeWorkflow()->cancel($appointment, 'Paciente canceló');

        $this->assertSame(AppointmentStatus::Cancelled, $result->status);
        $this->assertNotNull($result->cancelled_at);
        $this->assertStringContainsString('cancelación', $result->notes);
    }

    public function test_complete_does_not_sync_to_calendar(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->calendarService->expects($this->never())
            ->method('createOrUpdateEvent');

        $this->calendarService->expects($this->never())
            ->method('deleteEvent');

        $result = $this->makeWorkflow()->complete($appointment);

        $this->assertSame(AppointmentStatus::Completed, $result->status);
        $this->assertNotNull($result->completed_at);
    }

    public function test_mark_no_show_deletes_event(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'external_appointment_id' => 'event-to-delete',
        ]);

        $this->calendarService->expects($this->once())
            ->method('deleteEvent')
            ->with($appointment);

        $result = $this->makeWorkflow()->markNoShow($appointment);

        $this->assertSame(AppointmentStatus::NoShow, $result->status);
        $this->assertNotNull($result->no_show_at);
    }

    public function test_sync_to_calendar_delegates_to_calendar_service(): void
    {
        $appointment = Appointment::factory()->create();

        $this->calendarService->expects($this->once())
            ->method('syncAppointment')
            ->with($appointment)
            ->willReturn('synced-event-id');

        $result = $this->makeWorkflow()->syncToCalendar($appointment);

        $this->assertSame('synced-event-id', $result);
    }

    public function test_cancel_without_external_id_does_not_call_delete(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Scheduled,
            'external_appointment_id' => null,
        ]);

        $this->calendarService->expects($this->never())
            ->method('deleteEvent');

        $result = $this->makeWorkflow()->cancel($appointment);

        $this->assertSame(AppointmentStatus::Cancelled, $result->status);
    }
}
