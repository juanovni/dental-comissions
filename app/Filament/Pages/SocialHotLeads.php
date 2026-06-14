<?php

namespace App\Filament\Pages;

use App\Enums\SocialConversionStatus;
use App\Models\SocialComment;
use App\Services\SocialCrmSettingsService;
use App\Services\SocialLeadOperationsService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class SocialHotLeads extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Leads calientes';

    protected static ?string $title = 'Leads calientes';

    protected static ?string $slug = 'social-hot-leads';

    protected static ?int $navigationSort = 18;

    protected string $view = 'filament.pages.social-hot-leads';

    public function leads(): LengthAwarePaginator
    {
        return app(SocialLeadOperationsService::class)
            ->queryActionableLeads()
            ->orderByRaw('case when follow_up_at is not null and follow_up_at <= now() then 0 when hot_lead_at is not null then 1 when reheated_at is not null then 2 else 3 end')
            ->orderByDesc('interest_score')
            ->latest('created_at')
            ->paginate(10);
    }

    public function stats(): array
    {
        $service = app(SocialLeadOperationsService::class);
        $maxHoursWithoutContact = app(SocialCrmSettingsService::class)->salesMaxHoursWithoutContact();
        $overdueCutoff = now()->subHours($maxHoursWithoutContact);

        return [
            'total' => $service->queryActionableLeads()->count(),
            'overdue' => $service->queryActionableLeads()
                ->whereNull('contacted_at')
                ->whereRaw('coalesce(hot_lead_at, reheated_at, whatsapp_redirected_at, created_at) <= ?', [$overdueCutoff])
                ->count(),
            'follow_up_due' => $service->queryActionableLeads()
                ->whereNotNull('follow_up_at')
                ->where('follow_up_at', '<=', now())
                ->count(),
            'pending_patient' => $service->queryActionableLeads()
                ->where('conversion_status', SocialConversionStatus::PendingPatientCreation->value)
                ->count(),
        ];
    }

    public function markContacted(int $commentId): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        app(SocialLeadOperationsService::class)->markContacted($comment);
        $this->notify('Lead contactado', 'El lead salio de la cola operativa.');
    }

    public function scheduleFollowUp(int $commentId): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        $hours = app(SocialCrmSettingsService::class)->salesDefaultFollowUpHours();
        app(SocialLeadOperationsService::class)->scheduleFollowUp($comment, $hours);
        $this->notify('Seguimiento programado', "Volvera a la cola en {$hours} horas.");
    }

    public function markLost(int $commentId): void
    {
        $comment = $this->findComment($commentId);

        if (! $comment) {
            return;
        }

        app(SocialLeadOperationsService::class)->markLost($comment);
        $this->notify('Lead perdido', 'El lead fue cerrado y auditado.');
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

    private function notify(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->send();
    }
}
