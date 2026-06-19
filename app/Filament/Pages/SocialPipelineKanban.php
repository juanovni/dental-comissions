<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialPipelineStage;
use App\Models\SocialComment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class SocialPipelineKanban extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Pipeline Kanban';

    protected static ?string $title = 'Pipeline Comercial';

    protected static ?string $slug = 'social-pipeline-kanban';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.social-pipeline-kanban';

    public string $search = '';

    public ?int $lostModalCommentId = null;

    public string $lostReason = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function columns(): array
    {
        return [
            SocialPipelineStage::New,
            SocialPipelineStage::Qualified,
            SocialPipelineStage::Appointment,
            SocialPipelineStage::Proposal,
            SocialPipelineStage::Won,
            SocialPipelineStage::Lost,
        ];
    }

    public function cards(SocialPipelineStage $stage): Collection
    {
        return SocialComment::query()
            ->with(['socialIdentity.patient', 'socialAccount', 'suggestedProcedure'])
            ->where('pipeline_stage', $stage)
            ->where('is_hidden', false)
            ->when($this->search, fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->where('comment_text', 'ilike', "%{$this->search}%")
                    ->orWhere('author_name', 'ilike', "%{$this->search}%")
                    ->orWhere('author_username', 'ilike', "%{$this->search}%");
            }))
            ->orderByDesc('interest_score')
            ->latest('updated_at')
            ->get();
    }

    public function stageTotals(): array
    {
        $totals = SocialComment::totalEstimatedValueByStage();

        $result = [];
        foreach ($this->columns() as $stage) {
            $result[$stage->value] = (float) ($totals[$stage->value] ?? 0);
        }

        return $result;
    }

    public function stageCounts(): array
    {
        $counts = SocialComment::query()
            ->selectRaw('pipeline_stage, count(*) as cnt')
            ->whereNotNull('pipeline_stage')
            ->where('is_hidden', false)
            ->groupBy('pipeline_stage')
            ->pluck('cnt', 'pipeline_stage')
            ->toArray();

        $result = [];
        foreach ($this->columns() as $stage) {
            $result[$stage->value] = (int) ($counts[$stage->value] ?? 0);
        }

        return $result;
    }

    #[On('move-card')]
    public function moveCard(int $commentId, string $toStage): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        $stage = SocialPipelineStage::tryFrom($toStage);

        if (! $stage) {
            return;
        }

        if ($stage === SocialPipelineStage::Lost) {
            $this->lostModalCommentId = $commentId;

            return;
        }

        $this->applyStageChange($comment, $stage);
    }

    public function confirmLost(): void
    {
        $comment = $this->findComment($this->lostModalCommentId);

        if (! $comment) {
            return;
        }

        $this->applyStageChange($comment, SocialPipelineStage::Lost, $this->lostReason);
        $this->closeLostModal();
    }

    public function closeLostModal(): void
    {
        $this->lostModalCommentId = null;
        $this->lostReason = '';
    }

    public function updateEstimatedValue(int $commentId, ?float $value): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        $comment->update(['estimated_value' => $value]);

        $comment->actions()->create([
            'action' => SocialCommentActionType::LeadScoreUpdated,
            'performed_by' => auth()->id(),
            'notes' => 'Valor estimado actualizado.',
            'external_response' => ['estimated_value' => $value],
        ]);
    }

    private function applyStageChange(SocialComment $comment, SocialPipelineStage $stage, ?string $lostReason = null): void
    {
        $previousStage = $comment->pipeline_stage;

        $data = ['pipeline_stage' => $stage];

        if ($stage === SocialPipelineStage::Lost) {
            $data['lost_at'] = now();
            $data['lost_reason'] = $lostReason ?: null;
            $data['conversion_status'] = \App\Enums\SocialConversionStatus::Lost;
        }

        if ($stage === SocialPipelineStage::Won) {
            $data['conversion_status'] = \App\Enums\SocialConversionStatus::Converted;
            $data['converted_at'] ??= now();
        }

        $fromLabel = $previousStage?->label() ?? 'sin etapa';
        $toLabel = $stage->label();

        $comment->update($data);

        $comment->actions()->create([
            'action' => SocialCommentActionType::PipelineStageChanged,
            'performed_by' => auth()->id(),
            'notes' => "Movido de {$fromLabel} a {$toLabel}.",
            'external_response' => [
                'from' => $previousStage?->value,
                'to' => $stage->value,
                'lost_reason' => $lostReason,
            ],
        ]);

        Notification::make()
            ->title("Lead movido a {$toLabel}")
            ->success()
            ->send();
    }

    private function findComment(int $commentId): ?SocialComment
    {
        $comment = SocialComment::find($commentId);

        if (! $comment) {
            Notification::make()
                ->title('Lead no encontrado')
                ->danger()
                ->send();
        }

        return $comment;
    }
}
