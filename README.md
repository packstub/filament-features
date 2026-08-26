# Filament Features

<div class="filament-hidden">

![Filament Features — plans, limits and flags per tenant](https://packstub.dev/images/docs/filament-features/banner.jpg)

</div>

Feature flags and entitlements for Filament v5 tenants: define features and plans in code, store per-tenant overrides and global kill switches, check limits against real usage, and gate pages, actions, routes and Blade with one call.

- **Features & plans in code** — boolean features and numeric limits with defaults; plans bundle values; the plan comes from any source (a column, a subscription).
- **Overrides** — per-tenant trials, beta access and negotiated limits with expiry and reason; global overrides as kill switches or rollouts.
- **Usage-aware limits** — `remaining()`, `canUse()`, `assertCanUse()` against a usage resolver; events when a limit is hit.
- **Gating everywhere** — `feature:` middleware, `@feature` Blade directive, `->requiresFeature()` on actions and form components, `RequiresFeature` trait for pages/resources, `<x-packstub-features::locked>` upgrade card.
- **UI** — a tenant "Your plan" page with usage bars and an upgrade CTA; admin pages for flags and overrides.
- **Caching, commands, events** — per-request memo plus optional cache store; `features:list`, `features:prune`.

## Installation

```bash
composer require packstub/filament-features
php artisan packstub-features:install
```

```php
use Packstub\Features\FeaturesPlugin;

$panel->plugin(
    FeaturesPlugin::make()
        ->features([
            'api_access' => ['label' => 'API access', 'type' => 'boolean', 'default' => false, 'group' => 'Integrations'],
            'projects'   => ['label' => 'Projects', 'type' => 'limit', 'default' => 3, 'unit' => 'projects'],
        ])
        ->plans([
            'free' => ['label' => 'Free', 'features' => ['projects' => 3]],
            'pro'  => ['label' => 'Pro', 'price' => '$29', 'features' => ['projects' => 50, 'api_access' => true]],
        ], default: 'free')
        ->planUsing(fn (?Team $team) => $team?->subscription?->plan)   // or a `plan` column
        ->usage('projects', fn (?Team $team) => $team?->projects()->count() ?? 0)
        ->upgradeUrl(fn (?Team $team) => route('billing', $team)),
);
```

Optionally add `Packstub\Features\Concerns\HasFeatures` to the tenant model for `$team->hasFeature('api_access')`, `featureLimit()`, `featureRemaining()`, `canUseFeature()`.

## Checking

```php
Features::active('api_access');            // current Filament tenant
Features::active('api_access', $team);
Features::limit('projects', $team);        // 50, or null = unlimited
Features::remaining('projects', $team);
Features::canUse('projects', $team);
Features::assertCanUse('projects', $team); // throws FeatureLimitReached / FeatureInactive
Features::for($team)->snapshot();          // every feature with value, source, usage
```

```php
Route::get('/api/tokens', ...)->middleware('feature:api_access');          // 403
Route::get('/reports', ...)->middleware('feature:reports,redirect');       // → upgrade URL

Action::make('export')->requiresFeature('api_access');
TextInput::make('webhook_url')->requiresFeature('api_access');

class ReportsPage extends Page { use RequiresFeature; protected static string $feature = 'reports'; }
```

```blade
@feature('api_access') … @else … @endfeature
<x-packstub-features::locked feature="api_access" />
```

## Overrides

```php
Features::enable($team, 'reports', now()->addDays(14), 'trial');
Features::override($team, 'projects', 100, reason: 'enterprise deal');
Features::override($team, 'projects', null);        // unlimited
Features::disable(null, 'api_access');              // global kill switch
Features::clearOverride($team, 'reports');
```

Resolution order: **scope override → plan → global override → default**.

## Pages

- `Plan` (tenant panel, `/plan`) — "Your plan": features by group, usage bars, upgrade button. Disable with `->planPage(null)`.
- `FeatureFlags` and `FeatureOverrides` (admin panel) — `->adminPages()`.

## With Filament Teams

```php
TeamsPlugin::make()->seatLimit(fn (Team $team) => Features::limit('seats', $team));
```

## Configuration

See `config/packstub-features.php`: `scope_model`, `features`, `plans`, `default_plan`, `overrides_table`, `connection`, `cache.store` / `ttl`, `upgrade_url`. Anything can be overridden per panel with `->config([...])`.

## Testing

```bash
composer test
```

## License

Commercial — see [LICENSE.md](LICENSE.md). Support: support@packstub.dev.
