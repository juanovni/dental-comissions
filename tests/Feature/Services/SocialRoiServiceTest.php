<?php

namespace Tests\Feature\Services;

use App\Enums\ActivityStatus;
use App\Enums\ProfessionalRole;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPlatform;
use App\Models\ActivityRecord;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialIdentity;
use App\Models\SocialPost;
use App\Services\SocialRoiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialRoiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_activity_links_social_origin_and_updates_post_revenue(): void
    {
        $patient = Patient::create([
            'full_name' => 'Maria Perez',
            'normalized_name' => 'maria perez',
            'phone' => '+573001112233',
        ]);

        $doctor = Professional::factory()->create([
            'role' => ProfessionalRole::Doctor,
            'is_active' => true,
        ]);

        $procedure = Procedure::factory()->create([
            'name' => 'Implante dental',
            'internal_rate' => 2000,
            'is_active' => true,
        ]);

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_roi',
            'is_active' => true,
        ]);

        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_roi_1',
            'caption' => 'Campana de implantes',
        ]);

        $identity = SocialIdentity::create([
            'patient_id' => $patient->id,
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'ig_user_roi',
            'username' => 'maria_roi',
            'display_name' => 'Maria Perez',
            'status' => SocialIdentityStatus::LinkedPatient,
            'linked_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_roi_1',
            'author_name' => 'Maria Perez',
            'author_username' => 'maria_roi',
            'author_external_id' => 'ig_user_roi',
            'comment_text' => 'Quiero implantes',
            'conversion_status' => SocialConversionStatus::IdentityLinked,
            'converted_patient_id' => $patient->id,
            'converted_at' => now(),
        ]);

        $activity = ActivityRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'activity_date' => now()->toDateString(),
            'status' => ActivityStatus::Approved,
            'internal_rate_snapshot' => 2000,
        ]);

        app(SocialRoiService::class)->attributeActivity($activity);

        $this->assertDatabaseHas('activity_records', [
            'id' => $activity->id,
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
        ]);

        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
            'revenue_generated' => 2000,
            'conversion_count' => 1,
        ]);

        $this->assertDatabaseHas('social_comments', [
            'id' => $comment->id,
            'conversion_status' => SocialConversionStatus::Converted->value,
        ]);
    }
}
