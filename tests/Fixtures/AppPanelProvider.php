<?php

namespace Packstub\Features\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Packstub\Features\FeaturesPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->tenant(Team::class, slugAttribute: 'slug')
            ->pages([Dashboard::class, ReportsPage::class])
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
            ->authMiddleware([Authenticate::class])
            ->plugin(
                FeaturesPlugin::make()
                    ->features([
                        'api_access' => ['label' => 'API access', 'type' => 'boolean', 'default' => false, 'group' => 'Integrations', 'description' => 'Personal access tokens and webhooks.'],
                        'reports' => ['label' => 'Reports', 'default' => false, 'group' => 'Insights'],
                        'projects' => ['label' => 'Projects', 'type' => 'limit', 'default' => 3, 'unit' => 'projects', 'group' => 'Workspace'],
                        'storage_gb' => ['label' => 'Storage', 'type' => 'limit', 'default' => 1, 'unit' => 'GB'],
                    ])
                    ->plans([
                        'free' => ['label' => 'Free', 'features' => ['projects' => 3]],
                        'pro' => ['label' => 'Pro', 'price' => '$29', 'features' => ['projects' => 50, 'api_access' => true, 'reports' => true]],
                        'enterprise' => ['label' => 'Enterprise', 'features' => ['projects' => null, 'api_access' => true, 'reports' => true, 'storage_gb' => 500]],
                    ], default: 'free')
                    ->usage('projects', fn (?Team $team): int => $team?->projects()->count() ?? 0)
                    ->upgradeUrl(fn (?Team $team): string => '/billing/'.($team?->slug ?? 'x'))
                    ->adminPages()
                    ->canManageUsing(fn (User $user): bool => (bool) $user->is_admin)
                    ->navigationGroup('Plan'),
            );
    }
}
