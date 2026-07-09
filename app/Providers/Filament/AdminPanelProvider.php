<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DashboardRoiSocial;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): HtmlString => new HtmlString('<div class="me-2">'.view('filament.partials.social-lead-notification-center')->render().'</div>'),
        );

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl(fn (): string => DashboardRoiSocial::getUrl())
            ->login()
            ->brandLogo(function () {
                if (request()->routeIs('filament.admin.auth.login')) {
                    return '/images/logo-odon-crm_2.png';
                }
                return '/images/logo-odon-crm_2.png';
            })
            ->brandLogoHeight(function () {
                return request()->routeIs('filament.admin.auth.login') ? '3rem' : '1.35rem';
            })
            ->colors([
                'primary' => Color::Teal,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Dashboards')
                    ->icon('heroicon-o-chart-bar-square'),
                NavigationGroup::make('Reputacion Digital')
                    ->collapsible(false),
                NavigationGroup::make('CRM de Ventas')
                    ->icon('heroicon-o-briefcase'),
                NavigationGroup::make('Configuración')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->maxContentWidth('fi-width-full')
            ->viteTheme('resources/css/app.css')
            ->assets([
                Js::make('admin-app')
                    ->html(Vite::asset('resources/js/app.js'))
                    ->module(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
