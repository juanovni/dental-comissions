<?php

namespace Tests\Feature\Filament;

use App\Enums\SocialCommentClassification;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Enums\SocialPlatform;
use App\Filament\Pages\SocialPipelineKanban;
use App\Filament\Resources\SocialComments\Pages\ListSocialComments;
use App\Models\Procedure;
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

        $this->assertSame(1, app(SocialPipelineKanban::class)->cards(SocialPipelineStage::Qualified->value)->count());
    }

    public function test_nuevos_column_shows_only_new_leads(): void
    {
        $new = $this->socialComment([
            'comment_text' => 'Lead nuevo',
            'pipeline_stage' => SocialPipelineStage::New,
            'recent_engagement_score' => 10,
        ]);
        $qualified = $this->socialComment([
            'comment_text' => 'Lead calificado',
            'pipeline_stage' => SocialPipelineStage::Qualified,
            'recent_engagement_score' => 120,
        ]);

        $newCards = app(SocialPipelineKanban::class)->cards(SocialPipelineStage::New->value);
        $qualifiedCards = app(SocialPipelineKanban::class)->cards(SocialPipelineStage::Qualified->value);

        $this->assertTrue($newCards->contains($new));
        $this->assertFalse($newCards->contains($qualified));
        $this->assertTrue($qualifiedCards->contains($qualified));
        $this->assertFalse($qualifiedCards->contains($new));
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

    public function test_card_can_move_to_qualified(): void
    {
        $comment = $this->socialComment([
            'pipeline_stage' => SocialPipelineStage::New,
            'conversion_status' => SocialConversionStatus::None,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->call('moveCard', $comment->id, SocialPipelineStage::Qualified->value);

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

    public function test_pipeline_kanban_opens_selected_lead_detail(): void
    {
        $comment = $this->socialComment([
            'comment_text' => 'Lead abierto desde campana',
            'recent_engagement_score' => 85,
            'interest_score' => 90,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(SocialPipelineKanban::class)
            ->call('openLeadDetail', $comment->id)
            ->assertSet('selectedLeadId', $comment->id)
            ->assertSee('Pulso comercial')
            ->assertSee('Lead abierto desde campana');
    }

    public function test_social_comments_table_exposes_create_appointment_action(): void
    {
        $user = User::factory()->create();
        $procedure = Procedure::factory()->create(['name' => 'Implantes dentales']);
        $comment = $this->socialComment([
            'suggested_procedure_id' => $procedure->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListSocialComments::class)
            ->assertTableActionExists('create_appointment', record: $comment->getKey());
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
