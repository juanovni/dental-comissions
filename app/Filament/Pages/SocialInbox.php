<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialReputationRisk;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\SocialComment;
use App\Services\SocialConversionService;
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

    protected static ?string $navigationLabel = 'Smart Inbox';

    protected static ?string $title = 'Smart Inbox';

    protected static ?string $slug = 'social-inbox';

    protected static ?int $navigationSort = 19;

    protected string $view = 'filament.pages.social-inbox';

    public string $filter = 'leads';

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
            ->when($this->filter === 'crisis', fn (Builder $query): Builder => $this->applyCrisisQuery($query))
            ->when($this->filter === 'leads', fn (Builder $query): Builder => $query->whereIn('classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ]))
            ->when($this->filter === 'vip', fn (Builder $query): Builder => $query
                ->whereHas('socialIdentity.patient')
                ->whereHas('socialIdentity.patient.activityRecords'))
            ->when($this->filter === 'medical', fn (Builder $query): Builder => $query->where(
                'classification',
                SocialCommentClassification::MedicalSensitive->value,
            ))
            ->orderByRaw("case when reputation_risk = 'critical' then 0 when reputation_risk = 'high' then 1 when requires_human_review then 2 when priority = 'high' then 3 else 4 end")
            ->latest('created_at')
            ->paginate(8);
    }

    public function stats(): array
    {
        return [
            'leads' => SocialComment::whereIn('classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ])->count(),
            'crisis' => $this->applyCrisisQuery(SocialComment::query())->count(),
            'vip' => SocialComment::whereHas('socialIdentity.patient')
                ->whereHas('socialIdentity.patient.activityRecords')
                ->count(),
            'medical' => SocialComment::where('classification', SocialCommentClassification::MedicalSensitive->value)->count(),
            'all' => SocialComment::count(),
        ];
    }

    public function routeToWhatsapp(int $commentId): void
    {
        $comment = SocialComment::find($commentId);

        if (!$comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        $token = app(SocialConversionService::class)->markRedirectedToWhatsapp($comment);

        Notification::make()
            ->title('Lead derivado a WhatsApp')
            ->body("Token generado: {$token}")
            ->success()
            ->send();
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
            ->with([
                'convertedPatient',
                'convertedPatient.activityRecords.doctor',
                'convertedPatient.activityRecords.procedure',
                'socialAccount',
                'socialIdentity.patient.activityRecords.doctor',
                'socialIdentity.patient.activityRecords.procedure',
                'socialPost',
                'suggestedProcedure',
            ])
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

    private function applyCrisisQuery(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('reputation_risk', [
                SocialReputationRisk::High->value,
                SocialReputationRisk::Critical->value,
            ])->orWhereIn('classification', [
                SocialCommentClassification::Complaint->value,
                SocialCommentClassification::NegativeOpinion->value,
                SocialCommentClassification::LegalSensitive->value,
            ]);
        });
    }
}
