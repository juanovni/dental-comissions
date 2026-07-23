<?php

namespace App\Filament\Pages;

use App\Services\PatientFlowService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ManagerialDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?string $navigationLabel = 'Gerencial';

    protected static ?string $title = 'Dashboard Gerencial';

    protected static ?string $slug = 'managerial-dashboard';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.managerial-dashboard';

    #[Url(as: 'date')]
    public ?string $selectedDate = null;

    public function mount(): void
    {
        $this->selectedDate ??= today()->toDateString();
    }

    public function today(): void
    {
        $this->selectedDate = today()->toDateString();
    }

    public function kpis(): array
    {
        $date = Carbon::parse($this->selectedDate);

        return app(PatientFlowService::class)->getKpis($date);
    }
}
