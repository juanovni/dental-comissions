<?php

namespace App\Filament\Pages;

use App\Enums\SocialCommentActionType;
use App\Enums\SocialCommentClassification;
use App\Enums\SocialCommentStatus;
use App\Enums\SocialReputationRisk;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\Procedure;
use App\Models\SocialComment;
use App\Services\GeminiJsonService;
use App\Services\SocialConversionService;
use App\Services\SocialCrmSettingsService;
use App\Services\SocialLinkEventMapper;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class SocialInbox extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Bandeja social';

    protected static ?string $title = 'Bandeja social';

    protected static ?string $slug = 'social-inbox';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.social-inbox';

    public static function getNavigationBadge(): ?string
    {
        $archivedStatuses = app(SocialCrmSettingsService::class)->archivedConversionStatuses();

        $count = SocialComment::query()
            ->where(fn (Builder $query): Builder => static::applyExternalAuthorQuery($query))
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

    public int|string|null $whatsappProcedureId = null;

    public array $whatsappProcedureOptions = [];

    public array $smartLinkPreview = [];

    public bool $whatsappGenerated = false;

    public ?int $recentActivityLeadId = null;

    public ?int $selectedCommentId = null;

    public ?int $historicalSuggestionCommentId = null;

    public ?string $historicalReplySuggestion = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->selectedCommentId = null;
        $this->resetPage();
    }

    #[On('echo-private:admin-notifications,LeadActivityDetected')]
    public function handleLeadActivityDetected(array $payload): void
    {
        $leadId = isset($payload['lead_id']) ? (int) $payload['lead_id'] : null;

        $this->recentActivityLeadId = $leadId ?: null;
        $this->selectedCommentId = $leadId ?: $this->selectedCommentId;
        $this->resetPage();
    }

    public function selectComment(int $commentId): void
    {
        $this->selectedCommentId = $commentId;
        $this->historicalSuggestionCommentId = null;
        $this->historicalReplySuggestion = null;
    }

    public function closeCommentDrawer(): void
    {
        $this->selectedCommentId = null;
        $this->historicalSuggestionCommentId = null;
        $this->historicalReplySuggestion = null;
    }

    public function selectedComment(?int $fallbackId = null): ?SocialComment
    {
        $commentId = $this->selectedCommentId ?: $fallbackId;

        if (! $commentId) {
            return null;
        }

        return $this->baseQuery()
            ->with(['actions' => fn ($query) => $query->latest()->limit(6), 'leadAlerts' => fn ($query) => $query->latest()->limit(6)])
            ->find($commentId);
    }

    public function timelineEvents(int $commentId): array
    {
        return SocialComment::find($commentId)?->linkEvents()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($event): array => [
                'label' => SocialLinkEventMapper::label($event->event_type),
                'type' => $event->event_type,
                'icon' => SocialLinkEventMapper::icon($event->event_type),
                'color' => SocialLinkEventMapper::color($event->event_type),
                'group' => SocialLinkEventMapper::group($event->event_type),
                'progress' => SocialLinkEventMapper::progress($event->event_type),
                'date' => $event->created_at?->diffForHumans(),
                'duration' => $event->duration_seconds,
            ])
            ->all() ?? [];
    }

    public function suggestHistoricalReply(int $commentId): void
    {
        $comment = SocialComment::query()
            ->with(['actions', 'linkEvents', 'suggestedProcedure'])
            ->find($commentId);

        if (! $comment) {
            Notification::make()->title('Lead no encontrado')->danger()->send();

            return;
        }

        $eventSummary = $comment->linkEvents
            ->sortBy('created_at')
            ->map(fn ($event): string => '- '.SocialLinkEventMapper::label($event->event_type).' '.$event->created_at?->format('Y-m-d H:i'))
            ->implode("\n");
        $actionSummary = $comment->actions
            ->sortBy('created_at')
            ->map(fn ($action): string => '- '.$action->action->label().': '.($action->notes ?: $action->response_text ?: 'sin detalle'))
            ->implode("\n");

        try {
            $response = app(GeminiJsonService::class)->generate(
                'Eres un asesor comercial dental. Devuelve JSON con respuesta, tono y proxima_accion.',
                "Comentario original: {$comment->comment_text}\nProcedimiento: ".($comment->suggestedProcedure?->name ?? 'Sin procedimiento')."\nEstado: ".($comment->conversion_status?->label() ?? 'Sin estado')."\nEventos:\n{$eventSummary}\nAcciones previas:\n{$actionSummary}",
            );
            $data = json_decode($response, true);
            $suggestion = is_array($data)
                ? 'Respuesta: '.($data['respuesta'] ?? 'Sin respuesta')."\nTono: ".($data['tono'] ?? 'No definido')."\nProxima accion: ".($data['proxima_accion'] ?? 'No definida')
                : $response;
        } catch (\Throwable) {
            $suggestion = 'Respuesta: Hola, gracias por escribirnos. Vi que te interesa '.($comment->suggestedProcedure?->name ?? 'un tratamiento dental').". Podemos orientarte por WhatsApp y ayudarte a resolver tus dudas sin compromiso.\nTono: cercano y claro\nProxima accion: enviar Smart Link o derivar a WhatsApp para seguimiento.";
        }

        $comment->actions()->create([
            'action' => SocialCommentActionType::Reply,
            'performed_by' => auth()->id(),
            'notes' => 'Sugerencia IA basada en historial generada desde bandeja split-view.',
            'response_text' => $suggestion,
            'external_response' => ['source' => 'social_inbox_history_suggestion'],
        ]);

        $this->historicalSuggestionCommentId = $comment->id;
        $this->historicalReplySuggestion = $suggestion;

        Notification::make()
            ->title('Sugerencia generada')
            ->body('La respuesta basada en historial fue auditada en el lead.')
            ->success()
            ->send();
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
            ->orderByDesc('recent_engagement_score')
            ->orderByDesc('last_engagement_at')
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
        $comment = SocialComment::query()
            ->with(['socialPost.procedure', 'suggestedProcedure'])
            ->find($commentId);

        if (! $comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        $this->whatsappCommentId = $comment->id;
        $this->whatsappProcedureOptions = Procedure::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
        $this->whatsappProcedureId = $comment->suggested_procedure_id
            ?? $comment->socialPost?->procedure_id
            ?? $this->suggestProcedureId($comment);
        $this->whatsappGenerated = filled($comment->tracking_token);
        $this->whatsappToken = $comment->tracking_token ?: 'Se generara al confirmar';
        $this->whatsappLink = $comment->tracking_token ? (app(SocialConversionService::class)->whatsappLink($comment) ?? '') : '';
        $this->smartLink = $comment->tracking_token ? app(SocialConversionService::class)->smartLink($comment) : '';
        $this->whatsappReplyText = $comment->tracking_token ? app(SocialConversionService::class)->instagramReplyText($comment) : '';
        $this->refreshSmartLinkPreview();
        $this->whatsappModalOpen = true;
    }

    public function updatedWhatsappProcedureId(): void
    {
        $this->refreshSmartLinkPreview();
    }

    public function confirmWhatsappRouting(): void
    {
        if (! $this->whatsappCommentId) {
            return;
        }

        $comment = SocialComment::find($this->whatsappCommentId);

        if (! $comment) {
            Notification::make()
                ->title('Comentario no encontrado')
                ->danger()
                ->send();

            return;
        }

        $procedureId = filled($this->whatsappProcedureId) ? (int) $this->whatsappProcedureId : null;

        $data = ['suggested_procedure_id' => $procedureId];

        if ($comment->estimated_value === null && $procedureId) {
            $procedure = Procedure::find($procedureId);

            if ($procedure?->internal_rate !== null) {
                $data['estimated_value'] = $procedure->internal_rate;
            }
        }

        $comment->update($data);

        $conversionService = app(SocialConversionService::class);
        $token = $conversionService->markRedirectedToWhatsapp($comment->refresh());
        $comment->refresh();

        $this->whatsappToken = $token;
        $this->whatsappLink = $conversionService->whatsappLink($comment) ?? '';
        $this->smartLink = $conversionService->smartLink($comment);
        $this->whatsappReplyText = $conversionService->instagramReplyText($comment);
        $this->whatsappGenerated = true;

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

    private function refreshSmartLinkPreview(): void
    {
        $procedure = filled($this->whatsappProcedureId) ? Procedure::find((int) $this->whatsappProcedureId) : null;
        $category = strtolower((string) ($procedure?->category ?: $procedure?->code ?: 'unknown'));
        $normalizedCategory = str($category)->ascii()->lower()->replace([' ', '-'], '_')->toString();
        $blocks = app(SocialCrmSettingsService::class)->smartLinkContentBlocks();
        $content = $blocks[$normalizedCategory]
            ?? $blocks[$category]
            ?? $blocks['unknown']
            ?? [
                'eyebrow' => 'Valoracion dental personalizada',
                'title' => 'Tu sonrisa merece un plan claro, humano y sin presion.',
                'subtitle' => 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.',
                'visual_label' => 'Diagnostico integral',
                'visual_image_url' => '',
                'before_image_url' => '',
                'before_video_url' => '',
                'after_image_url' => '',
                'after_video_url' => '',
                'video_url' => '',
            ];

        $this->smartLinkPreview = [
            'procedure' => $procedure?->name ?: 'Sin definir',
            'category' => $procedure ? $normalizedCategory : 'unknown',
            'uses_unknown' => ! $procedure || (! isset($blocks[$normalizedCategory]) && ! isset($blocks[$category])),
            'eyebrow' => (string) ($content['eyebrow'] ?? 'Valoracion dental personalizada'),
            'title' => (string) ($content['title'] ?? 'Tu sonrisa merece un plan claro, humano y sin presion.'),
            'subtitle' => (string) ($content['subtitle'] ?? 'Mira como trabajamos y continua por WhatsApp para recibir orientacion de la clinica.'),
            'visual_label' => (string) ($content['visual_label'] ?? 'Diagnostico integral'),
            'visual_image_url' => (string) ($content['visual_image_url'] ?? ''),
            'before_image_url' => (string) ($content['before_image_url'] ?? ''),
            'before_video_url' => (string) ($content['before_video_url'] ?? ''),
            'after_image_url' => (string) ($content['after_image_url'] ?? ''),
            'after_video_url' => (string) ($content['after_video_url'] ?? ''),
            'video_url' => (string) ($content['video_url'] ?? ''),
        ];
    }

    private function suggestProcedureId(SocialComment $comment): ?int
    {
        $haystack = str($comment->comment_text.' '.$comment->socialPost?->caption)->ascii()->lower()->toString();

        $category = match (true) {
            str_contains($haystack, 'implante'),
            str_contains($haystack, 'diente perdido'),
            str_contains($haystack, 'pieza perdida') => 'implantes',
            str_contains($haystack, 'limpieza'),
            str_contains($haystack, 'profilaxis'),
            str_contains($haystack, 'sarro') => 'limpieza',
            str_contains($haystack, 'invisalign'),
            str_contains($haystack, 'alineador'),
            str_contains($haystack, 'ortodoncia invisible'),
            str_contains($haystack, 'bracket') => 'invisalign',
            str_contains($haystack, 'diseno de sonrisa'),
            str_contains($haystack, 'carilla'),
            str_contains($haystack, 'estetica'),
            str_contains($haystack, 'blanqueamiento') => 'diseno_sonrisa',
            default => null,
        };

        if (! $category) {
            return null;
        }

        return Procedure::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($category): void {
                $query->where('category', $category)
                    ->orWhere('code', $category);
            })
            ->value('id');
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
            ->where(fn (Builder $query): Builder => static::applyExternalAuthorQuery($query))
            ->where('is_hidden', false)
            ->when($archivedStatuses !== [], fn (Builder $query): Builder => $query->whereNotIn('conversion_status', $archivedStatuses));
    }

    private function applyArchivedQuery(Builder $query): Builder
    {
        $archivedStatuses = app(SocialCrmSettingsService::class)->archivedConversionStatuses();

        return $query
            ->where(fn (Builder $query): Builder => static::applyExternalAuthorQuery($query))
            ->where(function (Builder $query) use ($archivedStatuses): void {
                $query->where('is_hidden', true)
                    ->when($archivedStatuses !== [], fn (Builder $query): Builder => $query->orWhereIn('conversion_status', $archivedStatuses));
            });
    }

    private function baseQuery(): Builder
    {
        return SocialComment::query()
            ->where(fn (Builder $query): Builder => static::applyExternalAuthorQuery($query))
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
            })
            ->when($this->recentActivityLeadId, fn (Builder $query): Builder => $query->orderByRaw('case when social_comments.id = ? then 0 else 1 end', [$this->recentActivityLeadId]));
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

    private static function applyExternalAuthorQuery(Builder $query): Builder
    {
        return $query->whereDoesntHave('socialAccount', function (Builder $query): void {
            $query->whereColumn('social_accounts.external_account_id', 'social_comments.author_external_id')
                ->orWhereColumn('social_accounts.page_id', 'social_comments.author_external_id')
                ->orWhereColumn('social_accounts.instagram_business_account_id', 'social_comments.author_external_id')
                ->orWhereRaw("lower(replace(social_comments.author_username, '@', '')) = lower(replace(social_accounts.account_name, '@', ''))")
                ->orWhereRaw("lower(replace(social_comments.author_name, '@', '')) = lower(replace(social_accounts.account_name, '@', ''))");
        });
    }
}
