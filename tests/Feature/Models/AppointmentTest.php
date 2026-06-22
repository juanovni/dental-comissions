<?php

namespace Tests\Feature\Models;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_keeps_social_attribution_context(): void
    {
        $patient = Patient::factory()->create();
        $procedure = Procedure::factory()->create(['name' => 'Implantes dentales']);
        [$comment, $identity, $post] = $this->socialLead();
        $user = User::factory()->create();

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'procedure_id' => $procedure->id,
            'assigned_user_id' => $user->id,
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 45,
            'status' => AppointmentStatus::Scheduled,
            'source' => AppointmentSource::WhatsappAi,
            'metadata' => ['tracking_token' => $comment->tracking_token],
        ]);

        $this->assertTrue($appointment->patient->is($patient));
        $this->assertTrue($appointment->socialComment->is($comment));
        $this->assertTrue($appointment->socialIdentity->is($identity));
        $this->assertTrue($appointment->socialPost->is($post));
        $this->assertTrue($appointment->procedure->is($procedure));
        $this->assertTrue($appointment->assignedUser->is($user));
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertSame(AppointmentSource::WhatsappAi, $appointment->source);
        $this->assertSame('DNT-CITA1', $appointment->metadata['tracking_token']);
    }

    public function test_appointment_can_be_linked_to_external_provider(): void
    {
        $appointment = Appointment::factory()->create([
            'external_provider' => 'google_calendar',
            'external_appointment_id' => 'event-123',
            'external_calendar_id' => 'calendar-456',
            'external_status' => 'confirmed',
            'external_payload' => ['id' => 'event-123'],
            'last_synced_at' => now(),
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'external_provider' => 'google_calendar',
            'external_appointment_id' => 'event-123',
        ]);
        $this->assertSame('event-123', $appointment->external_payload['id']);
        $this->assertNotNull($appointment->last_synced_at);
    }

    public function test_appointment_status_helpers_set_timestamps(): void
    {
        $appointment = Appointment::factory()->create();

        $appointment->confirm();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->refresh()->status);
        $this->assertNotNull($appointment->confirmed_at);

        $appointment->complete();
        $this->assertSame(AppointmentStatus::Completed, $appointment->refresh()->status);
        $this->assertNotNull($appointment->completed_at);
    }

    private function socialLead(): array
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Video de implantes dentales',
        ]);

        $identity = SocialIdentity::create([
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'user_'.uniqid(),
            'username' => 'paciente_test',
            'display_name' => 'Paciente Test',
            'status' => SocialIdentityStatus::NewLead,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero agendar una valoracion',
            'tracking_token' => 'DNT-CITA1',
        ]);

        return [$comment, $identity, $post];
    }
}
