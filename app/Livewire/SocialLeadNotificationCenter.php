<?php

namespace App\Livewire;

use App\Filament\Pages\SocialPipelineKanban;
use App\Models\SocialLeadAlert;
use App\Services\SocialLeadAlertService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SocialLeadNotificationCenter extends Component
{
    public string $filter = 'all';

    public bool $urgentPulse = false;

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'danger', 'warning', 'info'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    #[On('echo-private:admin-notifications,LeadActivityDetected')]
    public function handleLeadActivityDetected(array $payload): void
    {
        $interestScore = (int) ($payload['interest_score'] ?? 0);
        $recentScore = (int) ($payload['recent_engagement_score'] ?? 0);

        if ($interestScore >= 70 || $recentScore >= 70) {
            $this->urgentPulse = true;
        }

        unset($this->alerts, $this->stats);
    }

    #[On('echo-private:admin-notifications,ClosingOpportunityDetected')]
    public function handleClosingOpportunityDetected(array $payload): void
    {
        $this->urgentPulse = true;

        unset($this->alerts, $this->stats);
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = SocialLeadAlert::whereNull('resolved_at')->find($alertId);

        if (! $alert) {
            Notification::make()
                ->title('Alerta no encontrada')
                ->danger()
                ->send();

            return;
        }

        app(SocialLeadAlertService::class)->resolve($alert, 'Alerta resuelta desde la campana.');

        unset($this->alerts, $this->stats);

        Notification::make()
            ->title('Alerta resuelta')
            ->success()
            ->send();
    }

    public function resolveAll(): void
    {
        $alerts = SocialLeadAlert::query()
            ->whereNull('resolved_at')
            ->get();

        $alerts->each(fn (SocialLeadAlert $alert) => app(SocialLeadAlertService::class)
            ->resolve($alert, 'Alerta resuelta masivamente desde la campana.'));

        unset($this->alerts, $this->stats);
        $this->urgentPulse = false;

        Notification::make()
            ->title('Alertas resueltas')
            ->body($alerts->count().' alertas archivadas.')
            ->success()
            ->send();
    }

    public function runChecks(): void
    {
        $summary = app(SocialLeadAlertService::class)->runScheduledChecks();

        unset($this->alerts, $this->stats);

        Notification::make()
            ->title('Revision ejecutada')
            ->body('Nuevas alertas: '.array_sum($summary))
            ->success()
            ->send();
    }

    public function leadUrl(SocialLeadAlert $alert): string
    {
        return SocialPipelineKanban::getUrl(['lead' => $alert->social_comment_id]);
    }

    #[Computed]
    public function alerts(): Collection
    {
        return $this->baseAlertQuery()
            ->when($this->filter !== 'all', fn (Builder $query) => $query->where('severity', $this->filter))
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'all' => SocialLeadAlert::whereNull('resolved_at')->count(),
            'danger' => SocialLeadAlert::whereNull('resolved_at')->where('severity', 'danger')->count(),
            'warning' => SocialLeadAlert::whereNull('resolved_at')->where('severity', 'warning')->count(),
            'info' => SocialLeadAlert::whereNull('resolved_at')->where('severity', 'info')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.social-lead-notification-center');
    }

    private function baseAlertQuery(): Builder
    {
        return SocialLeadAlert::query()
            ->with(['socialComment.socialIdentity.patient', 'socialComment.convertedPatient', 'socialComment.suggestedProcedure'])
            ->whereNull('resolved_at')
            ->orderByRaw("case when severity = 'danger' then 0 when severity = 'warning' then 1 else 2 end")
            ->latest('created_at');
    }
}
