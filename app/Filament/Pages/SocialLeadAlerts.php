<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Models\SocialLeadAlert;
use App\Services\SocialLeadAlertService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class SocialLeadAlerts extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'Reputacion Digital';

    protected static ?string $navigationLabel = 'Alertas de leads';

    protected static ?string $title = 'Alertas de leads';

    protected static ?string $slug = 'social-lead-alerts';

    protected static ?int $navigationSort = 16;

    protected string $view = 'filament.pages.social-lead-alerts';

    public string $filter = 'open';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function alerts(): LengthAwarePaginator
    {
        return SocialLeadAlert::query()
            ->with(['socialComment.socialIdentity.patient', 'socialComment.convertedPatient', 'socialComment.suggestedProcedure'])
            ->when($this->filter === 'open', fn ($query) => $query->whereNull('resolved_at'))
            ->when($this->filter === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->orderByRaw("case when severity = 'danger' then 0 when severity = 'warning' then 1 else 2 end")
            ->latest('created_at')
            ->paginate(12);
    }

    public function stats(): array
    {
        return [
            'open' => SocialLeadAlert::whereNull('resolved_at')->count(),
            'danger' => SocialLeadAlert::whereNull('resolved_at')->where('severity', 'danger')->count(),
            'warning' => SocialLeadAlert::whereNull('resolved_at')->where('severity', 'warning')->count(),
            'resolved' => SocialLeadAlert::whereNotNull('resolved_at')->count(),
        ];
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = SocialLeadAlert::find($alertId);

        if (! $alert) {
            Notification::make()->title('Alerta no encontrada')->danger()->send();

            return;
        }

        app(SocialLeadAlertService::class)->resolve($alert);

        Notification::make()
            ->title('Alerta resuelta')
            ->success()
            ->send();
    }

    public function runChecks(): void
    {
        $summary = app(SocialLeadAlertService::class)->runScheduledChecks();

        Notification::make()
            ->title('Revision ejecutada')
            ->body('Nuevas alertas: '.array_sum($summary))
            ->success()
            ->send();
    }

    public function detailUrl(SocialLeadAlert $alert): string
    {
        return SocialCommentResource::getUrl('view', ['record' => $alert->socialComment]);
    }
}
