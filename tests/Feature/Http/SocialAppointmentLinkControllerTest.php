<?php

namespace Tests\Feature\Http;

use App\Enums\AppointmentStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\Appointment;
use App\Models\AppointmentSlotOffer;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialCrmSetting;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAppointmentLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_link_shows_context_and_valid_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_show_doctor', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');
        $this->setting('social_appointment_lead_time_hours', 2, 'integer');

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Ana Morales']);
        $comment = $this->socialComment($procedure, $doctor);
        $offer = $this->offer($comment, [
            ['index' => 1, 'datetime' => '2026-07-15 10:00:00', 'doctor_id' => $doctor->id],
            ['index' => 2, 'datetime' => '2026-07-15 11:15:00', 'doctor_id' => $doctor->id],
        ]);

        $this->get(route('social-appointments.show', ['token' => $offer->token]))
            ->assertOk()
            ->assertSee('Ortodoncia invisible')
            ->assertSee('Dra. Ana Morales')
            ->assertSee('10:00 AM')
            ->assertSee('11:15 AM')
            ->assertSee('Confirmar cita');

        Carbon::setTestNow();
    }

    public function test_appointment_link_hides_doctor_when_setting_is_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_show_doctor', false, 'boolean');

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Ana Morales']);
        $comment = $this->socialComment($procedure, $doctor);
        $offer = $this->offer($comment, [
            ['index' => 1, 'datetime' => '2026-07-15 10:00:00', 'doctor_id' => $doctor->id],
        ]);

        $this->get(route('social-appointments.show', ['token' => $offer->token]))
            ->assertOk()
            ->assertSee('Ortodoncia invisible')
            ->assertDontSee('Dra. Ana Morales');

        Carbon::setTestNow();
    }

    public function test_appointment_link_filters_occupied_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 09:00:00'));

        $this->setting('social_appointment_show_doctor', true, 'boolean');
        $this->setting('social_appointment_slot_duration', 45, 'integer');

        $procedure = Procedure::factory()->create(['name' => 'Ortodoncia invisible']);
        $doctor = Professional::factory()->doctor()->create(['name' => 'Dra. Ana Morales']);
        $comment = $this->socialComment($procedure, $doctor);
        $offer = $this->offer($comment, [
            ['index' => 1, 'datetime' => '2026-07-15 10:00:00', 'doctor_id' => $doctor->id],
            ['index' => 2, 'datetime' => '2026-07-15 11:15:00', 'doctor_id' => $doctor->id],
        ]);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'scheduled_at' => '2026-07-15 10:00:00',
            'duration_minutes' => 45,
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->get(route('social-appointments.show', ['token' => $offer->token]))
            ->assertOk()
            ->assertDontSee('10:00 AM')
            ->assertSee('11:15 AM');

        Carbon::setTestNow();
    }

    private function setting(string $key, mixed $value, string $type): void
    {
        SocialCrmSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'value_type' => $type, 'label' => $key, 'is_active' => true],
        );
    }

    private function offer(SocialComment $comment, array $options): AppointmentSlotOffer
    {
        return AppointmentSlotOffer::create([
            'social_comment_id' => $comment->id,
            'token' => 'test-token-'.uniqid(),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'procedure_id' => $comment->suggested_procedure_id,
                'doctor_id' => $comment->suggested_doctor_id,
                'options' => $options,
            ],
        ]);
    }

    private function socialComment(Procedure $procedure, Professional $doctor): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Test',
            'external_account_id' => 'test_'.uniqid(),
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'procedure_id' => $procedure->id,
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
            'suggested_procedure_id' => $procedure->id,
            'suggested_doctor_id' => $doctor->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comm_'.uniqid(),
            'author_name' => 'Test User',
            'author_username' => 'test_user',
            'comment_text' => 'Quiero agendar',
            'tracking_token' => 'DNT-TEST'.uniqid(),
        ]);
    }
}
