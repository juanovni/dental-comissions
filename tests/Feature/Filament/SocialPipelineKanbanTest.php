<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialPipelineKanban;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialPipelineKanbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_kanban_shows_visible_comments_and_hides_hidden_comments(): void
    {
        $visible = $this->socialComment([
            'comment_text' => 'Lead visible en kanban',
            'is_hidden' => false,
        ]);
        $hidden = $this->socialComment([
            'comment_text' => 'Lead oculto en kanban',
            'is_hidden' => true,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->assertSee($visible->comment_text)
            ->assertDontSee($hidden->comment_text);

        $this->assertSame(1, app(SocialPipelineKanban::class)->cards('smart_inbox')->count());
    }

    public function test_smart_inbox_merges_new_and_qualified_and_orders_by_recent_engagement(): void
    {
        $qualified = $this->socialComment([
            'comment_text' => 'Lead calificado intenso',
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'recent_engagement_score' => 120,
            'last_engagement_at' => now(),
        ]);
        $new = $this->socialComment([
            'comment_text' => 'Lead nuevo frio',
            'conversion_status' => SocialConversionStatus::None,
            'pipeline_stage' => SocialPipelineStage::New,
            'recent_engagement_score' => 10,
            'last_engagement_at' => now()->subMinutes(5),
        ]);

        $cards = app(SocialPipelineKanban::class)->cards('smart_inbox');

        $this->assertTrue($cards->contains($qualified));
        $this->assertTrue($cards->contains($new));
        $this->assertSame($qualified->id, $cards->first()->id);
    }

    public function test_archive_drop_to_won_converts_directly(): void
    {
        $comment = $this->socialComment();

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->call('moveCard', $comment->id, SocialPipelineStage::Won->value);

        $comment->refresh();

        $this->assertSame(SocialPipelineStage::Won, $comment->pipeline_stage);
        $this->assertSame(SocialConversionStatus::Converted, $comment->conversion_status);
        $this->assertNotNull($comment->converted_at);
    }

    public function test_card_can_move_back_to_smart_inbox(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::Appointment,
            'conversion_status' => SocialConversionStatus::PendingPatientCreation,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->call('moveCard', $comment->id, 'smart_inbox');

        $this->assertSame(SocialPipelineStage::Qualified, $comment->refresh()->pipeline_stage);
    }

    public function test_archive_drop_to_lost_asks_for_reason_then_archives(): void
    {
        $comment = $this->socialComment();

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->call('moveCard', $comment->id, SocialPipelineStage::Lost->value)
            ->assertSet('lostModalCommentId', $comment->id)
            ->set('lostReason', 'No contesto')
            ->call('confirmLost');

        $comment->refresh();

        $this->assertSame(SocialPipelineStage::Lost, $comment->pipeline_stage);
        $this->assertSame(SocialConversionStatus::Lost, $comment->conversion_status);
        $this->assertSame('No contesto', $comment->lost_reason);
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
            'author_username' => 'paciente_test',
            'author_external_id' => 'user_'.uniqid(),
            'comment_text' => 'Quiero informacion',
            'classification' => SocialCommentClassification::SalesLead,
            'conversion_status' => SocialConversionStatus::TokenGenerated,
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'is_hidden' => false,
        ], $overrides));
    }
}
