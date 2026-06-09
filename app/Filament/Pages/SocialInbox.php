<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPriority;
use App\Enums\SocialReputationRisk;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\SocialComment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class SocialInbox extends Page
{
    use WithPagination;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Bandeja de Reputacion';

    protected static ?string $title = 'Bandeja de Reputacion';

    protected static ?string $slug = 'social-inbox';

    protected static ?int $navigationSort = 19;

    protected string $view = 'filament.pages.social-inbox';

    public string $filter = 'review';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function comments(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when($this->filter === 'review', fn (Builder $query): Builder => $query->where('requires_human_review', true))
            ->when($this->filter === 'high_risk', fn (Builder $query): Builder => $query->whereIn('reputation_risk', [
                SocialReputationRisk::High->value,
                SocialReputationRisk::Critical->value,
            ]))
            ->when($this->filter === 'leads', fn (Builder $query): Builder => $query->whereIn('classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ]))
            ->when($this->filter === 'complaints', fn (Builder $query): Builder => $query->whereIn('classification', [
                SocialCommentClassification::Complaint->value,
                SocialCommentClassification::NegativeOpinion->value,
                SocialCommentClassification::LegalSensitive->value,
            ]))
            ->when($this->filter === 'spam', fn (Builder $query): Builder => $query->whereIn('classification', [
                SocialCommentClassification::Spam->value,
                SocialCommentClassification::Offensive->value,
            ]))
            ->when($this->filter === 'facebook', fn (Builder $query): Builder => $query->where('platform', SocialPlatform::Facebook->value))
            ->when($this->filter === 'instagram', fn (Builder $query): Builder => $query->where('platform', SocialPlatform::Instagram->value))
            ->orderByRaw("case when reputation_risk = 'critical' then 0 when reputation_risk = 'high' then 1 when requires_human_review then 2 when priority = 'high' then 3 else 4 end")
            ->latest('created_at')
            ->paginate(8);
    }

    public function stats(): array
    {
        return [
            'review' => SocialComment::where('requires_human_review', true)->count(),
            'high_risk' => SocialComment::whereIn('reputation_risk', [
                SocialReputationRisk::High->value,
                SocialReputationRisk::Critical->value,
            ])->count(),
            'leads' => SocialComment::whereIn('classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ])->count(),
            'complaints' => SocialComment::whereIn('classification', [
                SocialCommentClassification::Complaint->value,
                SocialCommentClassification::NegativeOpinion->value,
                SocialCommentClassification::LegalSensitive->value,
            ])->count(),
            'spam' => SocialComment::whereIn('classification', [
                SocialCommentClassification::Spam->value,
                SocialCommentClassification::Offensive->value,
            ])->count(),
        ];
    }

    public function markReviewed(int $commentId): void
    {
        $this->applyAction(
            $commentId,
            SocialCommentActionType::MarkAsReviewed,
            SocialCommentStatus::Classified,
            'Comentario revisado desde la bandeja de reputacion.',
        );
    }

    public function ignore(int $commentId): void
    {
        $this->applyAction(
            $commentId,
            SocialCommentActionType::Ignore,
            SocialCommentStatus::Ignored,
            'Comentario ignorado desde la bandeja de reputacion.',
        );
    }

    public function escalate(int $commentId): void
    {
        $this->applyAction(
            $commentId,
            SocialCommentActionType::Escalate,
            SocialCommentStatus::Escalated,
            'Comentario escalado desde la bandeja de reputacion.',
        );
    }

    public function markSpam(int $commentId): void
    {
        $this->applyAction(
            $commentId,
            SocialCommentActionType::MarkAsSpam,
            SocialCommentStatus::MarkedAsSpam,
            'Comentario marcado como spam interno desde la bandeja de reputacion.',
        );
    }

    private function applyAction(
        int $commentId,
        SocialCommentActionType $action,
        SocialCommentStatus $status,
        string $notes,
    ): void {
        $comment = SocialComment::find($commentId);

        if (!$comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        SocialCommentResource::registerAction($comment, $action, $status, $notes);
    }

    private function baseQuery(): Builder
    {
        return SocialComment::query()
            ->with(['socialAccount', 'socialPost'])
            ->when($this->search !== '', function (Builder $query): Builder {
                $search = '%' . trim($this->search) . '%';

                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('comment_text', 'like', $search)
                        ->orWhere('author_name', 'like', $search)
                        ->orWhere('author_username', 'like', $search);
                });
            });
    }
}
