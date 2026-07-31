<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Widgets\WhatsappIssuesTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard operativo';

    protected static ?string $navigationLabel = 'Actividad y comisiones';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        $this->redirect(match (auth()->user()?->role) {
            UserRole::Receptionist => Reception::getUrl(),
            UserRole::Doctor => DoctorQueue::getUrl(),
            UserRole::Assistant => ClinicalQueue::getUrl(),
            UserRole::SuperAdmin, UserRole::Admin => DashboardRoiSocial::getUrl(),
            default => AppointmentResource::getUrl(),
        });
    }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            WhatsappIssuesTable::class,
        ];
    }
}
