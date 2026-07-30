<?php

namespace Tests\Feature\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Patient;
use App\Models\SocialCrmSetting;
use App\Services\AppointmentReminderService;
use App\Services\SocialCrmSettingsService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AppointmentReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_are_disabled_by_default(): void
    {
        $patient = Patient::factory()->create(['phone' => '+593985925100']);
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addHours(2),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->mock(WhatsappService::class, function ($mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $summary = app(AppointmentReminderService::class)->run(now());

        $this->assertSame(1, $summary['whatsapp_skipped']);
        $this->assertArrayNotHasKey('voice_skipped', $summary);
        $this->assertArrayNotHasKey('voice_queued', $summary);
        $this->assertDatabaseCount('appointment_reminders', 0);
    }

    public function test_whatsapp_reminder_is_sent_once_when_enabled(): void
    {
        $this->setSetting('appointment_reminders_whatsapp_enabled', true, 'boolean');

        $patient = Patient::factory()->create(['phone' => '+593985925100']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addHours(3),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->mock(WhatsappService::class, function ($mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('593985925100', Mockery::type('string'))
                ->andReturnTrue();
        });

        $service = app(AppointmentReminderService::class);

        $summary = $service->run(now());
        $secondRun = $service->run(now());

        $this->assertSame(1, $summary['whatsapp_first_sent']);
        $this->assertSame(0, $secondRun['whatsapp_first_sent']);
        $this->assertDatabaseHas('appointment_reminders', [
            'appointment_id' => $appointment->id,
            'channel' => 'whatsapp',
            'reminder_type' => 'first',
            'status' => 'sent',
            'to_phone' => '593985925100',
        ]);
    }

    public function test_no_response_alert_is_created_once_for_sent_whatsapp_reminder(): void
    {
        $this->setSetting('appointment_reminders_whatsapp_enabled', false, 'boolean');
        $this->setSetting('appointment_reminders_internal_alert_on_no_response', true, 'boolean');
        $this->setSetting('appointment_reminders_no_response_alert_minutes', 60, 'integer');

        $patient = Patient::factory()->create([
            'full_name' => 'Maria Perez',
            'phone' => '+593985925100',
        ]);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'scheduled_at' => now()->addHours(2),
            'status' => AppointmentStatus::Scheduled,
        ]);

        AppointmentReminder::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'channel' => 'whatsapp',
            'reminder_type' => 'first',
            'status' => 'sent',
            'to_phone' => '593985925100',
            'message' => 'Recordatorio',
            'scheduled_for' => $appointment->scheduled_at,
            'sent_at' => now()->subMinutes(61),
            'metadata' => ['appointment_status' => AppointmentStatus::Scheduled->value],
        ]);

        $this->mock(WhatsappService::class, function ($mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $service = app(AppointmentReminderService::class);

        $summary = $service->run(now());
        $secondRun = $service->run(now());

        $this->assertSame(1, $summary['no_response_alerts_created']);
        $this->assertSame(0, $secondRun['no_response_alerts_created']);
        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'note_type' => 'whatsapp_no_response',
            'is_pinned' => true,
        ]);
        $this->assertDatabaseCount('appointment_notes', 1);
    }

    private function setSetting(string $key, mixed $value, string $type): void
    {
        SocialCrmSetting::create([
            'setting_group' => 'appointment_reminders',
            'key' => $key,
            'label' => $key,
            'value_type' => $type,
            'value' => $value,
            'is_active' => true,
        ]);

        app(SocialCrmSettingsService::class)->clearCache();
    }
}
