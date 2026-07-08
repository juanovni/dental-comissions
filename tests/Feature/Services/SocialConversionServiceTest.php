<?php

namespace Tests\Feature\Services;

use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Patient;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Models\WhatsappMessage;
use App\Services\SocialConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_incoming_message_links_existing_patient_by_phone(): void
    {
        $patient = Patient::create([
            'full_name' => 'Maria Perez',
            'normalized_name' => 'maria perez',
            'phone' => '+573001112233',
        ]);

        $comment = $this->socialComment('DNT-ABCDE');
        $message = $this->whatsappMessage('+573001112233', 'Hola, mi codigo es DNT-ABCDE');

        $processed = app(SocialConversionService::class)->processIncomingMessage($message);

        $this->assertTrue($processed->is($comment));
        $this->assertDatabaseHas('social_identities', [
            'id' => $comment->social_identity_id,
            'patient_id' => $patient->id,
            'status' => SocialIdentityStatus::LinkedPatient->value,
        ]);
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'conversion_status' => SocialConversionStatus::IdentityLinked->value,
            'converted_patient_id' => $patient->id,
            'is_hidden' => true,
        ]);
    }

    public function test_process_incoming_message_marks_lead_pending_when_patient_does_not_exist(): void
    {
        $comment = $this->socialComment('DNT-XYZ12');
        $message = $this->whatsappMessage('+573009998877', 'DNT-XYZ12');

        app(SocialConversionService::class)->processIncomingMessage($message);

        $this->assertDatabaseHas('social_identities', [
            'id' => $comment->social_identity_id,
            'normalized_phone' => '573009998877',
            'status' => SocialIdentityStatus::PendingPatientCreation->value,
        ]);
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation->value,
            'converted_patient_id' => null,
            'is_hidden' => false,
        ]);
    }

    public function test_generate_tracking_token_sets_conversion_status(): void
    {
        $comment = $this->socialComment();

        $token = app(SocialConversionService::class)->generateTrackingToken($comment);

        $this->assertMatchesRegularExpression('/^DNT-[A-Z0-9]{5}$/', $token);
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'tracking_token' => $token,
            'conversion_status' => SocialConversionStatus::TokenGenerated->value,
            'interest_score' => 30,
        ]);
    }

    public function test_generate_tracking_token_scores_only_once(): void
    {
        $comment = $this->socialComment();

        $service = app(SocialConversionService::class);
        $firstToken = $service->generateTrackingToken($comment);
        $secondToken = $service->generateTrackingToken($comment->refresh());

        $this->assertSame($firstToken, $secondToken);
        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'interest_score' => 30,
        ]);
    }

    private function socialComment(?string $token = null): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_1',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.uniqid(),
            'caption' => 'Implantes dentales',
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

        return SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'author_username' => 'paciente_test',
            'author_external_id' => $identity->platform_user_id,
            'comment_text' => 'Quiero informacion',
            'tracking_token' => $token,
        ]);
    }

    private function whatsappMessage(string $fromPhone, string $body): WhatsappMessage
    {
        return WhatsappMessage::create([
            'direction' => WhatsappMessageDirection::Incoming,
            'status' => WhatsappMessageStatus::Received,
            'from_phone' => $fromPhone,
            'to_phone' => 'test-phone-number-id',
            'message_body' => $body,
            'message_sid' => 'wamid.'.uniqid(),
        ]);
    }
}
