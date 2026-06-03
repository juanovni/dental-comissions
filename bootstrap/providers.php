<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Livewire\LivewireServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

return [
    AppServiceProvider::class,
    BladeIconsServiceProvider::class,
    BladeHeroiconsServiceProvider::class,
    BladeCaptureDirectiveServiceProvider::class,
    LivewireServiceProvider::class,
    PowerJoinsServiceProvider::class,
    SupportServiceProvider::class,
    ActionsServiceProvider::class,
    SchemasServiceProvider::class,
    FormsServiceProvider::class,
    TablesServiceProvider::class,
    InfolistsServiceProvider::class,
    NotificationsServiceProvider::class,
    WidgetsServiceProvider::class,
    QueryBuilderServiceProvider::class,
    FilamentServiceProvider::class,
    AdminPanelProvider::class,
];
