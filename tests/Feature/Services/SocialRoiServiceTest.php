<?php

namespace Tests\Feature\Services;

use App\Enums\ActivityStatus;
use App\Enums\AppointmentStatus;
use App\Enums\ProfessionalRole;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialIdentityStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Models\ActivityRecord;
use App\Models\Appointment;
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

    public function test_summary_includes_social_appointment_metrics(): void
    {
        $patient = Patient::factory()->create();
        $doctor = Professional::factory()->create(['role' => ProfessionalRole::Doctor]);
        $procedure = Procedure::factory()->create(['internal_rate' => 1200]);
        [$comment, $identity, $post] = $this->socialLeadWithPost('appointment_metrics', $patient, $procedure);

        Appointment::create([
            'patient_id' => $patient->id,
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'procedure_id' => $procedure->id,
            'scheduled_at' => now()->addDay(),
            'status' => AppointmentStatus::Confirmed,
            'source' => 'whatsapp_ai',
        ]);

        ActivityRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'social_comment_id' => $comment->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'activity_date' => now()->toDateString(),
            'status' => ActivityStatus::Approved,
            'internal_rate_snapshot' => 1200,
        ]);

        $summary = app(SocialRoiService::class)->summary();
        $funnel = app(SocialRoiService::class)->funnelData();

        $this->assertSame(1, $summary['appointment_count']);
        $this->assertSame(1, $summary['appointment_confirmed_count']);
        $this->assertSame(0, $summary['appointment_leakage_count']);
        $this->assertSame(100.0, $summary['lead_to_appointment_rate']);
        $this->assertSame(100.0, $summary['whatsapp_to_appointment_rate']);
        $this->assertSame(100.0, $summary['appointment_to_activity_rate']);
        $this->assertSame(['Comentarios', 'WhatsApp', 'Ficha', 'Citas', 'Actividad'], $funnel['labels']);
        $this->assertSame([1, 1, 1, 1, 1], $funnel['values']);
    }

    public function test_appointment_leakage_query_detects_overdue_social_appointments_without_activity(): void
    {
        $patient = Patient::factory()->create();
        $doctor = Professional::factory()->create(['role' => ProfessionalRole::Doctor]);
        $procedure = Procedure::factory()->create();
        [$leakingComment, $leakingIdentity, $leakingPost] = $this->socialLeadWithPost('leaking_appointment', $patient, $procedure);
        [$resolvedComment, $resolvedIdentity, $resolvedPost] = $this->socialLeadWithPost('resolved_appointment', $patient, $procedure);

        $leaking = Appointment::create([
            'patient_id' => $patient->id,
            'social_comment_id' => $leakingComment->id,
            'social_identity_id' => $leakingIdentity->id,
            'social_post_id' => $leakingPost->id,
            'procedure_id' => $procedure->id,
            'scheduled_at' => now()->subDays(2),
            'status' => AppointmentStatus::Scheduled,
            'source' => 'whatsapp_ai',
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'social_comment_id' => $resolvedComment->id,
            'social_identity_id' => $resolvedIdentity->id,
            'social_post_id' => $resolvedPost->id,
            'procedure_id' => $procedure->id,
            'scheduled_at' => now()->subDays(2),
            'status' => AppointmentStatus::Scheduled,
            'source' => 'whatsapp_ai',
        ]);

        ActivityRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'procedure_id' => $procedure->id,
            'social_comment_id' => $resolvedComment->id,
            'social_identity_id' => $resolvedIdentity->id,
            'social_post_id' => $resolvedPost->id,
            'activity_date' => now()->toDateString(),
            'status' => ActivityStatus::Approved,
            'internal_rate_snapshot' => 100,
        ]);

        $leakage = app(SocialRoiService::class)->appointmentLeakageQuery()->get();

        $this->assertCount(1, $leakage);
        $this->assertTrue($leakage->first()->is($leaking));
        $this->assertSame(1, app(SocialRoiService::class)->summary()['appointment_leakage_count']);
    }

    public function test_appointment_performance_by_post_orders_by_appointment_count(): void
    {
        $patient = Patient::factory()->create();
        $procedure = Procedure::factory()->create();
        [$firstComment, $firstIdentity, $firstPost] = $this->socialLeadWithPost('post_top', $patient, $procedure);
        [$secondComment, $secondIdentity, $secondPost] = $this->socialLeadWithPost('post_low', $patient, $procedure);

        foreach (range(1, 2) as $index) {
            Appointment::create([
                'patient_id' => $patient->id,
                'social_comment_id' => $firstComment->id,
                'social_identity_id' => $firstIdentity->id,
                'social_post_id' => $firstPost->id,
                'procedure_id' => $procedure->id,
                'scheduled_at' => now()->addDays($index),
                'status' => AppointmentStatus::Scheduled,
                'source' => 'smart_link',
            ]);
        }

        Appointment::create([
            'patient_id' => $patient->id,
            'social_comment_id' => $secondComment->id,
            'social_identity_id' => $secondIdentity->id,
            'social_post_id' => $secondPost->id,
            'procedure_id' => $procedure->id,
            'scheduled_at' => now()->addDay(),
            'status' => AppointmentStatus::Scheduled,
            'source' => 'smart_link',
        ]);

        $rows = app(SocialRoiService::class)->appointmentPerformanceByPost();

        $this->assertSame($firstPost->id, $rows->first()->id);
        $this->assertSame(2, (int) $rows->first()->appointment_count);
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

    private function socialLeadWithPost(string $suffix, Patient $patient, Procedure $procedure): array
    {
        $account = $this->socialAccount('ig_account_'.$suffix);
        $post = SocialPost::create([
            'social_account_id' => $account->id,
            'procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_post_id' => 'post_'.$suffix,
            'caption' => 'Campana '.$suffix,
        ]);
        $identity = SocialIdentity::create([
            'patient_id' => $patient->id,
            'platform' => SocialPlatform::Instagram,
            'platform_user_id' => 'ig_user_'.$suffix,
            'username' => 'user_'.$suffix,
            'display_name' => $patient->full_name,
            'status' => SocialIdentityStatus::LinkedPatient,
            'linked_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        $comment = SocialComment::create([
            'social_account_id' => $account->id,
            'social_identity_id' => $identity->id,
            'social_post_id' => $post->id,
            'suggested_procedure_id' => $procedure->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.$suffix,
            'author_name' => $patient->full_name,
            'comment_text' => 'Quiero informacion',
            'tracking_token' => 'DNT-'.strtoupper(substr(md5($suffix), 0, 5)),
            'whatsapp_redirected_at' => now(),
            'conversion_status' => SocialConversionStatus::IdentityLinked,
            'converted_patient_id' => $patient->id,
            'converted_at' => now(),
        ]);

        return [$comment, $identity, $post];
    }
}
