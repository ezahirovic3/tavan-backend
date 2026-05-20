<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ListingsOverTimeChart;
use App\Filament\Widgets\QuickModerationQueue;
use App\Filament\Widgets\RecentActivityFeed;
use App\Filament\Widgets\TavanStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Brand mark is rendered as text — styled by theme.css so it
            // tracks currentColor across light/dark modes.
            ->brandName('TAVAN')
            ->favicon(asset('favicon.ico'))
            ->colors([
                // Pink is the only "living" colour. Reserved for primary actions,
                // active nav, and a single attention KPI.
                'primary' => Color::hex('#FB5C90'),
                // Green is brand-marketing only — used here for success states.
                'success' => Color::hex('#1D781C'),
                'warning' => Color::Amber,
                'danger'  => Color::Red,
                'gray'    => Color::Neutral,
                'info'    => Color::Slate,
            ])
            ->font('Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarFullyCollapsibleOnDesktop()
            ->topNavigation(false)
            ->maxContentWidth(Width::Full)
            ->breadcrumbs(true)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                NavigationGroup::make('Sadržaj'),
                NavigationGroup::make('Katalog'),
                NavigationGroup::make('Trgovina'),
                NavigationGroup::make('Moderacija'),
                NavigationGroup::make('Komunikacija'),
                NavigationGroup::make('Analitika'),
                NavigationGroup::make('Sistem'),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                TavanStatsOverview::class,
                ListingsOverTimeChart::class,
                RecentActivityFeed::class,
                QuickModerationQueue::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
