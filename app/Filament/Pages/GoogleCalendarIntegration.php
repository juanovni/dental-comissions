<?php

namespace App\Filament\Pages;

use App\Enums\ProfessionalRole;
use App\Models\Professional;
use App\Services\GoogleCalendarService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class GoogleCalendarIntegration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Google Calendar';

    protected static ?string $title = 'Google Calendar';

    protected static ?string $slug = 'integrations/google-calendar';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.google-calendar-integration';

    public function mount(): void
    {
        $this->redirect('/admin/integrations#google-calendar');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return new HtmlString('<span class="text-sm font-normal text-muted-foreground">Conecta la cuenta de Google de cada doctor para consultar disponibilidad, crear y actualizar eventos automáticamente. <strong>Importante:</strong> Las conexiones creadas antes del 7-Jul-2026 deben reconectarse para habilitar escritura en el calendario.</span>');
    }

    public function getPageClasses(): array
    {
        return ['gcal-integration-page'];
    }

    public function getDoctors(): array
    {
        $doctors = Professional::query()
            ->where('role', ProfessionalRole::Doctor)
            ->orderBy('name')
            ->get();

        return $doctors->map(function (Professional $doctor) {
            $service = app(GoogleCalendarService::class);

            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'google_calendar_email' => $doctor->google_calendar_email,
                'is_connected' => $doctor->hasGoogleCalendar(),
                'connect_url' => $service->getAuthorizationUrl($doctor),
            ];
        })->all();
    }

    public function disconnect(int $professionalId): void
    {
        $professional = Professional::find($professionalId);

        if (!$professional) {
            Notification::make()
                ->title('Profesional no encontrado')
                ->danger()
                ->send();
            return;
        }

        $success = app(GoogleCalendarService::class)->revokeToken($professional);

        if ($success) {
            Notification::make()
                ->title('Google Calendar desconectado')
                ->body("{$professional->name} ha sido desconectado de Google Calendar.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error al desconectar')
                ->danger()
                ->send();
        }
    }
}
