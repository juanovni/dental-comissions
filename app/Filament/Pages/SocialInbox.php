<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialReputationRisk;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\SocialComment;
use App\Services\SocialConversionService;
use App\Services\SocialCrmSettingsService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class SocialInbox extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Bandeja social';

    protected static ?string $title = 'Bandeja social';

    protected static ?string $slug = 'social-inbox';

    protected static ?int $navigationSort = 19;

    protected string $view = 'filament.pages.social-inbox';

    public static function getNavigationBadge(): ?string
    {
        $archivedStatuses = app(SocialCrmSettingsService::class)->archivedConversionStatuses();

        $count = SocialComment::query()
            ->where('is_hidden', false)
            ->when($archivedStatuses !== [], fn (Builder $query): Builder => $query->whereNotIn('conversion_status', $archivedStatuses))
            ->whereNotIn('status', [
                SocialCommentStatus::Hidden->value,
                SocialCommentStatus::Ignored->value,
                SocialCommentStatus::MarkedAsSpam->value,
                SocialCommentStatus::Responded->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public string $filter = 'leads';

    public string $search = '';

    public bool $whatsappModalOpen = false;

    public ?int $whatsappCommentId = null;

    public string $whatsappToken = '';

    public string $whatsappLink = '';

    public string $smartLink = '';

    public string $whatsappReplyText = '';

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
            ->when($this->filter === 'archived', fn (Builder $query): Builder => $this->applyArchivedQuery($query))
            ->when($this->filter !== 'archived', fn (Builder $query): Builder => $this->applyActiveQuery($query))
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
            ->orderByRaw("case when reputation_risk = 'critical' then 0 when reputation_risk = 'high' then 1 when hot_lead_at is not null then 2 when requires_human_review then 3 when priority = 'high' then 4 else 5 end")
            ->orderByDesc('interest_score')
            ->latest('created_at')
            ->paginate(8);
    }

    public function stats(): array
    {
        return [
            'leads' => $this->applyActiveQuery(SocialComment::query())->whereIn('classification', [
                SocialCommentClassification::SalesLead->value,
                SocialCommentClassification::CommercialQuestion->value,
            ])->count(),
            'crisis' => $this->applyCrisisQuery($this->applyActiveQuery(SocialComment::query()))->count(),
            'vip' => $this->applyActiveQuery(SocialComment::query())->whereHas('socialIdentity.patient')
                ->whereHas('socialIdentity.patient.activityRecords')
                ->count(),
            'medical' => $this->applyActiveQuery(SocialComment::query())->where('classification', SocialCommentClassification::MedicalSensitive->value)->count(),
            'all' => $this->applyActiveQuery(SocialComment::query())->count(),
            'archived' => $this->applyArchivedQuery(SocialComment::query())->count(),
        ];
    }

    public function routeToWhatsapp(int $commentId): void
    {
        $comment = SocialComment::find($commentId);

        if (! $comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        $conversionService = app(SocialConversionService::class);
        $token = $conversionService->markRedirectedToWhatsapp($comment);
        $comment->refresh();

        $this->whatsappCommentId = $comment->id;
        $this->whatsappToken = $token;
        $this->whatsappLink = $conversionService->whatsappLink($comment) ?? '';
        $this->smartLink = $conversionService->smartLink($comment);
        $this->whatsappReplyText = $conversionService->instagramReplyText($comment);
        $this->whatsappModalOpen = true;

        $copyText = $this->whatsappReplyText ?: $this->whatsappLink;

        if ($copyText !== '') {
            $this->dispatch('social-whatsapp-link-generated',
                text: $copyText,
                toast: 'Texto de seguimiento copiado. Pegalo como respuesta al comentario.',
            );
        }

        Notification::make()
            ->title('Lead derivado a WhatsApp')
            ->body("Texto de seguimiento copiado. Token: {$token}")
            ->success()
            ->send();
    }

    public function closeWhatsappModal(): void
    {
        $this->whatsappModalOpen = false;
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

        if (! $comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        SocialCommentResource::registerAction($comment, $action, $status, $notes);
    }

    private function applyActiveQuery(Builder $query): Builder
    {
        $archivedStatuses = app(SocialCrmSettingsService::class)->archivedConversionStatuses();

        return $query
            ->where('is_hidden', false)
            ->when($archivedStatuses !== [], fn (Builder $query): Builder => $query->whereNotIn('conversion_status', $archivedStatuses));
    }

    private function applyArchivedQuery(Builder $query): Builder
    {
        $archivedStatuses = app(SocialCrmSettingsService::class)->archivedConversionStatuses();

        return $query->where(function (Builder $query) use ($archivedStatuses): void {
            $query->where('is_hidden', true)
                ->when($archivedStatuses !== [], fn (Builder $query): Builder => $query->orWhereIn('conversion_status', $archivedStatuses));
        });
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
                $search = '%'.trim($this->search).'%';

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
