<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialConversionStatus;
use App\Enums\SocialPipelineStage;
use App\Models\SocialComment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
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

    #[Url(as: 'lead')]
    public ?int $selectedLeadId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function columns(): array
    {
        return [
            'smart_inbox' => 'Smart Inbox',
            SocialPipelineStage::Appointment->value => SocialPipelineStage::Appointment->label(),
            SocialPipelineStage::Proposal->value => SocialPipelineStage::Proposal->label(),
        ];
    }

    public function cards(string $stage): Collection
    {
        return SocialComment::query()
            ->with(['socialIdentity.patient', 'socialAccount', 'suggestedProcedure'])
            ->when(
                $stage === 'smart_inbox',
                fn (Builder $query): Builder => $query->whereIn('pipeline_stage', [
                    SocialPipelineStage::New->value,
                    SocialPipelineStage::Qualified->value,
                ]),
                fn (Builder $query): Builder => $query->where('pipeline_stage', $stage),
            )
            ->where('is_hidden', false)
            ->when($this->search, fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->where('comment_text', 'ilike', "%{$this->search}%")
                    ->orWhere('author_name', 'ilike', "%{$this->search}%")
                    ->orWhere('author_username', 'ilike', "%{$this->search}%");
            }))
            ->orderByDesc('recent_engagement_score')
            ->orderByDesc('last_engagement_at')
            ->orderByDesc('interest_score')
            ->latest('updated_at')
            ->get();
    }

    public function stageTotals(): array
    {
        $result = [];
        foreach (array_keys($this->columns()) as $stage) {
            $result[$stage] = (float) $this->baseStageQuery($stage)->sum('estimated_value');
        }

        return $result;
    }

    public function stageCounts(): array
    {
        $result = [];
        foreach (array_keys($this->columns()) as $stage) {
            $result[$stage] = $this->baseStageQuery($stage)->count();
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

        $stage = $toStage === 'smart_inbox'
            ? SocialPipelineStage::Qualified
            : SocialPipelineStage::tryFrom($toStage);

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

    public function openLeadDetail(int $commentId): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        $this->selectedLeadId = $comment->id;
    }

    public function closeLeadDetail(): void
    {
        $this->selectedLeadId = null;
    }

    public function selectedLead(): ?SocialComment
    {
        if (! $this->selectedLeadId) {
            return null;
        }

        return SocialComment::query()
            ->with(['socialIdentity.patient', 'convertedPatient', 'socialAccount', 'suggestedProcedure', 'leadAlerts' => fn ($query) => $query->whereNull('resolved_at')->latest()])
            ->where('is_hidden', false)
            ->find($this->selectedLeadId);
    }

    private function applyStageChange(SocialComment $comment, SocialPipelineStage $stage, ?string $lostReason = null): void
    {
        $previousStage = $comment->pipeline_stage;

        $data = ['pipeline_stage' => $stage];

        if ($stage === SocialPipelineStage::Lost) {
            $data['lost_at'] = now();
            $data['lost_reason'] = $lostReason ?: null;
            $data['conversion_status'] = SocialConversionStatus::Lost;
        }

        if ($stage === SocialPipelineStage::Won) {
            $data['conversion_status'] = SocialConversionStatus::Converted;
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

    private function baseStageQuery(string $stage): Builder
    {
        return SocialComment::query()
            ->where('is_hidden', false)
            ->when(
                $stage === 'smart_inbox',
                fn (Builder $query): Builder => $query->whereIn('pipeline_stage', [
                    SocialPipelineStage::New->value,
                    SocialPipelineStage::Qualified->value,
                ]),
                fn (Builder $query): Builder => $query->where('pipeline_stage', $stage),
            );
    }
}
