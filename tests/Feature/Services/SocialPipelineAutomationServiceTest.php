<?php

namespace Tests\Feature\Services;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialLinkEvent;
use App\Services\SocialPipelineAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPipelineAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_engagement_moves_new_lead_to_qualified(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::New,
            'recent_engagement_score' => 55,
        ]);
        $event = $this->linkEvent($comment, 'video_complete');

        $updated = app(SocialPipelineAutomationService::class)->applyEngagement($comment, $event);

        $this->assertSame(SocialPipelineStage::Qualified, $updated->pipeline_stage);
        $this->assertDatabaseHas('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::PipelineStageChanged->value,
            'notes' => 'Movido automaticamente a calificado por engagement reciente.',
        ]);
    }

    public function test_whatsapp_click_moves_lead_to_appointment(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'recent_engagement_score' => 40,
        ]);
        $event = $this->linkEvent($comment, 'whatsapp_click');

        $updated = app(SocialPipelineAutomationService::class)->applyEngagement($comment, $event);

        $this->assertSame(SocialPipelineStage::Appointment, $updated->pipeline_stage);
        $this->assertSame(SocialConversionStatus::WhatsappStarted, $updated->conversion_status);
    }

    public function test_agent_ready_to_book_moves_lead_to_appointment(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
        ]);

        $updated = app(SocialPipelineAutomationService::class)->applyAgentResponse($comment, [
            'intent' => 'ready_to_book',
            'closing_opportunity_score' => 85,
            'suggested_pipeline_stage' => 'appointment',
            'clinical_safety_flag' => false,
        ]);

        $this->assertSame(SocialPipelineStage::Appointment, $updated->pipeline_stage);
        $this->assertSame(SocialConversionStatus::WhatsappStarted, $updated->conversion_status);
    }

    public function test_terminal_leads_are_not_moved_automatically(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::Lost,
            'conversion_status' => SocialConversionStatus::Lost,
            'recent_engagement_score' => 200,
        ]);
        $event = $this->linkEvent($comment, 'whatsapp_click');

        $updated = app(SocialPipelineAutomationService::class)->applyEngagement($comment, $event);

        $this->assertSame(SocialPipelineStage::Lost, $updated->pipeline_stage);
        $this->assertSame(SocialConversionStatus::Lost, $updated->conversion_status);
        $this->assertDatabaseMissing('social_comment_actions', [
            'social_comment_id' => $comment->id,
            'action' => SocialCommentActionType::PipelineStageChanged->value,
        ]);
    }

    public function test_clinical_safety_agent_response_does_not_move_pipeline(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
        ]);

        $updated = app(SocialPipelineAutomationService::class)->applyAgentResponse($comment, [
            'intent' => 'medical_sensitive',
            'closing_opportunity_score' => 90,
            'suggested_pipeline_stage' => 'appointment',
            'clinical_safety_flag' => true,
        ]);

        $this->assertSame(SocialPipelineStage::Qualified, $updated->pipeline_stage);
        $this->assertSame(SocialConversionStatus::TokenGenerated, $updated->conversion_status);
    }

    private function socialComment(array $overrides = []): SocialComment
    {
        $account = SocialAccount::create([
            'platform' => SocialPlatform::Instagram,
            'account_name' => 'Clinica Dental',
            'external_account_id' => 'ig_account_'.uniqid(),
            'is_active' => true,
        ]);

        return SocialComment::create(array_merge([
            'social_account_id' => $account->id,
            'platform' => SocialPlatform::Instagram,
            'external_comment_id' => 'comment_'.uniqid(),
            'author_name' => 'Paciente Test',
            'comment_text' => 'Quiero informacion',
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'pipeline_stage' => SocialPipelineStage::New,
            'tracking_token' => 'DNT-'.strtoupper(substr(uniqid(), -5)),
        ], $overrides));
    }

    private function linkEvent(SocialComment $comment, string $type): SocialLinkEvent
    {
        return SocialLinkEvent::create([
            'social_comment_id' => $comment->id,
            'event_type' => $type,
            'session_id' => 'test-session',
        ]);
    }
}
