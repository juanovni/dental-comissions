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

        $this->assertSame(1, app(SocialPipelineKanban::class)->cards(SocialPipelineStage::Qualified)->count());
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
