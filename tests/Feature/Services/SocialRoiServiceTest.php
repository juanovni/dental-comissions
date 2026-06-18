<?php

namespace Tests\Feature\Services;

use App\Enums\ActivityStatus;
use App\Enums\ProfessionalRole;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
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
use Illuminate\Support\Facades\Storage;
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

    public function test_weekly_leakage_report_includes_high_value_lost_leads(): void
    {
        $account = $this->socialAccount('ig_account_leakage');
        $procedure = Procedure::factory()->create(['name' => 'Implante premium']);

        $included = SocialComment::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'lost_high_value',
            'author_name' => 'Lead Perdido',
            'comment_text' => 'Me interesa el implante',
            'suggested_procedure_id' => $procedure->id,
            'pipeline_stage' => SocialPipelineStage::Lost,
            'estimated_value' => 2500,
            'lost_reason' => 'precio',
            'lost_at' => now()->startOfWeek()->addDay(),
        ]);

        SocialComment::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'lost_low_value',
            'author_name' => 'Lead Bajo',
            'comment_text' => 'Consulta',
            'pipeline_stage' => SocialPipelineStage::Lost,
            'estimated_value' => 500,
            'lost_reason' => 'tiempo',
            'lost_at' => now()->startOfWeek()->addDay(),
        ]);

        $report = app(SocialRoiService::class)->weeklyLeakageReport(now()->startOfWeek(), 1000);

        $this->assertSame(1, $report['total_leads']);
        $this->assertSame(2500.0, $report['total_value']);
        $this->assertSame($included->id, $report['leads']->first()['id']);
        $this->assertSame('Implante premium', $report['leads']->first()['procedure']);
        $this->assertSame('local', $report['audit']['source']);
    }

    public function test_roi_leakage_report_command_writes_pdf(): void
    {
        Storage::fake('local');
        $account = $this->socialAccount('ig_account_command');

        SocialComment::create([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'lost_command',
            'author_name' => 'Lead PDF',
            'comment_text' => 'Quiero presupuesto',
            'pipeline_stage' => SocialPipelineStage::Lost,
            'estimated_value' => 1800,
            'lost_reason' => 'financiacion',
            'lost_at' => now()->startOfWeek()->addDay(),
        ]);

        $this->artisan('social:roi-leakage-report', [
            '--week' => now()->toDateString(),
            '--output' => 'testing/fuga.pdf',
        ])->assertSuccessful();

        Storage::disk('local')->assertExists('testing/fuga.pdf');
    }

    private function socialAccount(string $externalAccountId): SocialAccount
    {
        return SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => $externalAccountId,
            'is_active' => true,
        ]);
    }
}
