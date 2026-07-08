<?php

namespace Tests\Feature\Services;

use App\Models\Appointment;
use App\Models\Professional;
use App\Services\AppointmentAvailabilityService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AppointmentAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppointmentAvailabilityService::class);

        config([
            'services.google_oauth' => [
                'client_id' => 'test-client-id.apps.googleusercontent.com',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'https://example.com/auth/google/callback',
                'scopes' => [
                    'https://www.googleapis.com/auth/calendar.readonly',
                    'https://www.googleapis.com/auth/userinfo.email',
                ],
                'access_type' => 'offline',
                'prompt' => 'consent',
            ],
        ]);
    }

    public function test_next_available_slots_returns_slots(): void
    {
        $slots = $this->service->nextAvailableSlots(3);

        $this->assertNotEmpty($slots);
        $this->assertCount(3, $slots);
        foreach ($slots as $slot) {
            $this->assertInstanceOf(Carbon::class, $slot);
            $this->assertTrue($slot->isFuture());
        }
    }

    public function test_next_available_slots_excludes_existing_appointments(): void
    {
        $existingSlot = $this->service->nextAvailableSlots(1)[0];

        Appointment::create([
            'scheduled_at' => $existingSlot,
            'duration_minutes' => 45,
            'status' => 'scheduled',
            'source' => 'admin_manual',
        ]);

        $slots = $this->service->nextAvailableSlots(3);

        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertNotEquals(
                $existingSlot->timestamp,
                $slot->timestamp,
                'El slot ocupado no deberia aparecer'
            );
        }
    }

    public function test_next_available_slots_for_doctor_without_google_calendar(): void
    {
        $professional = Professional::factory()->create([
            'role' => 'doctor',
            'google_calendar_enabled' => false,
        ]);

        $slots = $this->service->nextAvailableSlotsForDoctor($professional, 3);

        $this->assertNotEmpty($slots);
        $this->assertCount(3, $slots);
    }

    public function test_next_available_slots_for_doctor_excludes_own_appointments_only(): void
    {
        $doctor = Professional::factory()->doctor()->create();
        $otherDoctor = Professional::factory()->doctor()->create();

        $slot = $this->service->nextAvailableSlotsForDoctor($doctor, 1)[0];

        Appointment::create([
            'doctor_id' => $otherDoctor->id,
            'scheduled_at' => $slot,
            'duration_minutes' => 45,
            'status' => 'scheduled',
            'source' => 'admin_manual',
        ]);

        $slots = $this->service->nextAvailableSlotsForDoctor($doctor, 3);

        $this->assertContains($slot->timestamp, collect($slots)->map->timestamp->all());

        Appointment::create([
            'doctor_id' => $doctor->id,
            'scheduled_at' => $slot,
            'duration_minutes' => 45,
            'status' => 'scheduled',
            'source' => 'admin_manual',
        ]);

        $slots = $this->service->nextAvailableSlotsForDoctor($doctor, 3);

        $this->assertNotContains($slot->timestamp, collect($slots)->map->timestamp->all());
    }

    public function test_is_slot_available_for_doctor_detects_conflicts_by_doctor(): void
    {
        $doctor = Professional::factory()->doctor()->create();
        $otherDoctor = Professional::factory()->doctor()->create();

        $slot = $this->service->nextAvailableSlotsForDoctor($doctor, 1)[0];
        $slotEnd = $slot->copy()->addMinutes(45);

        Appointment::create([
            'doctor_id' => $otherDoctor->id,
            'scheduled_at' => $slot,
            'duration_minutes' => 45,
            'status' => 'scheduled',
            'source' => 'admin_manual',
        ]);

        $this->assertTrue($this->service->isSlotAvailableForDoctor($doctor, $slot, $slotEnd));

        Appointment::create([
            'doctor_id' => $doctor->id,
            'scheduled_at' => $slot,
            'duration_minutes' => 45,
            'status' => 'scheduled',
            'source' => 'admin_manual',
        ]);

        $this->assertFalse($this->service->isSlotAvailableForDoctor($doctor, $slot, $slotEnd));
    }

    public function test_next_available_slots_for_doctor_filters_by_google_calendar(): void
    {
        $token = [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created' => now()->timestamp,
        ];

        $professional = Professional::factory()->create([
            'role' => 'doctor',
            'google_calendar_enabled' => true,
            'google_calendar_token' => Crypt::encryptString(json_encode($token)),
            'google_calendar_token_expires_at' => now()->addHour(),
        ]);

        $busySlot = now()->addHours(2)->startOfHour();

        $mockService = $this->createMock(GoogleCalendarService::class);
        $mockService->method('isSlotAvailable')
            ->willReturnCallback(function ($prof, $start, $end) use ($busySlot) {
                return $start->timestamp !== $busySlot->timestamp;
            });

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $slots = $this->service->nextAvailableSlotsForDoctor($professional, 5);

        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertNotEquals(
                $busySlot->timestamp,
                $slot->timestamp,
                'Slot ocupado en Google Calendar no deberia aparecer'
            );
        }
    }

    public function test_format_slots_for_prompt_empty(): void
    {
        $result = $this->service->formatSlotsForPrompt([]);

        $this->assertSame('No hay horarios disponibles proximamente.', $result);
    }

    public function test_format_slots_for_prompt_with_slots(): void
    {
        $slots = [now()->addDay()->setTime(10, 0)];

        $result = $this->service->formatSlotsForPrompt($slots);

        $this->assertStringContainsString('Manana', $result);
        $this->assertStringContainsString('10:00', $result);
    }
}
