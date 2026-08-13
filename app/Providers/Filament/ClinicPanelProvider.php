<?php

namespace App\Providers\Filament;

use App\Enums\UserRole;
use App\Filament\Pages\ClinicalOperations;
use App\Filament\Pages\ClinicalQueue;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DashboardRoiSocial;
use App\Filament\Pages\DoctorQueue;
use App\Filament\Pages\GoogleCalendarIntegration;
use App\Filament\Pages\Integrations;
use App\Filament\Pages\Reception;
use App\Filament\Pages\SocialCrmSettings;
use App\Filament\Pages\SocialHotLeads;
use App\Filament\Pages\SocialInbox;
use App\Filament\Pages\SocialPipelineKanban;
use App\Filament\Pages\VoiceTestSimulator;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\DoctorAssistantAssignments\DoctorAssistantAssignmentResource;
use App\Filament\Resources\LocalLanguagePatterns\LocalLanguagePatternResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Procedures\ProcedureResource;
use App\Filament\Resources\Professionals\ProfessionalResource;
use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use App\Filament\Resources\SocialComments\SocialCommentResource;
use App\Filament\Resources\SocialCrmSettings\SocialCrmSettingResource;
use App\Filament\Resources\VoiceCalls\VoiceCallResource;
use App\Http\Middleware\EnsureTenantMatchesHost;
use App\Http\Middleware\ResolveClinicFromHost;
use App\Http\Middleware\SyncFilamentTenantContext;
use App\Models\Clinic;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClinicPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): HtmlString => new HtmlString('<div class="">'.view('filament.partials.social-lead-notification-center')->render().'</div>'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => in_array(auth()->user()?->role, [UserRole::Receptionist, UserRole::Assistant, UserRole::Doctor], true)
                ? new HtmlString(<<<'HTML'
                    <script>
                        document.documentElement.classList.add('fi-role-clinical-flat')

                        try {
                            const collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups') || '[]')

                            localStorage.setItem(
                                'collapsedGroups',
                                JSON.stringify(collapsedGroups.filter((group) => group !== 'Operación Clinica')),
                            )
                        } catch (error) {
                            localStorage.removeItem('collapsedGroups')
                        }
                    </script>
                    HTML)
                : new HtmlString(''),
        );

        return $panel
            ->id('clinic')
            ->path('clinic')
            ->tenant(Clinic::class, slugAttribute: 'slug')
            ->homeUrl(function (): string {
                return match (auth()->user()?->role) {
                    UserRole::Receptionist => Reception::getUrl(panel: 'clinic'),
                    UserRole::Doctor => DoctorQueue::getUrl(panel: 'clinic'),
                    UserRole::Assistant => ClinicalQueue::getUrl(panel: 'clinic'),
                    UserRole::SuperAdmin, UserRole::Admin => DashboardRoiSocial::getUrl(panel: 'clinic'),
                    default => AppointmentResource::getUrl(panel: 'clinic'),
                };
            })
            ->login()
            ->brandLogo('/images/icon-odon-crm_3.png')
            ->brandLogoHeight('1.35rem')
            ->colors([
                'primary' => [
                    50 => 'oklch(97% .02 185)',
                    100 => 'oklch(94% .035 185)',
                    200 => 'oklch(88% .055 185)',
                    300 => 'oklch(80% .08 185)',
                    400 => 'oklch(68% .105 185)',
                    500 => 'oklch(55% .12 185)',
                    600 => 'oklch(47% .115 185)',
                    700 => 'oklch(39% .1 185)',
                    800 => 'oklch(31% .08 185)',
                    900 => 'oklch(25% .06 185)',
                    950 => 'oklch(18% .045 185)',
                ],
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Dashboards'),
                NavigationGroup::make('Reputacion Digital')->collapsible(false),
                NavigationGroup::make('Operación Clinica')
                    ->collapsible(fn (): bool => ! in_array(auth()->user()?->role, [UserRole::Receptionist, UserRole::Assistant, UserRole::Doctor], true)),
                NavigationGroup::make('Pity Voice'),
                NavigationGroup::make('Configuración'),
            ])
            ->maxContentWidth('fi-width-full')
            ->viteTheme('resources/css/app.css')
            ->assets([
                Js::make('clinic-app')
                    ->html(Vite::asset('resources/js/app.js'))
                    ->module(),
            ])
            ->resources([
                AppointmentResource::class,
                DoctorAssistantAssignmentResource::class,
                LocalLanguagePatternResource::class,
                PatientResource::class,
                ProcedureResource::class,
                ProfessionalResource::class,
                SocialAccountResource::class,
                SocialCommentResource::class,
                SocialCrmSettingResource::class,
                VoiceCallResource::class,
            ])
            ->pages([
                Dashboard::class,
                DashboardRoiSocial::class,
                Reception::class,
                DoctorQueue::class,
                ClinicalQueue::class,
                ClinicalOperations::class,
                Integrations::class,
                GoogleCalendarIntegration::class,
                SocialInbox::class,
                SocialPipelineKanban::class,
                SocialHotLeads::class,
                SocialCrmSettings::class,
                VoiceTestSimulator::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ResolveClinicFromHost::class,
                IdentifyTenant::class,
                EnsureTenantMatchesHost::class,
                SyncFilamentTenantContext::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
