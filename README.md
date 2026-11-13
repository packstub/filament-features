# Filament Features

<div class="filament-hidden">

![Filament Features — plans, limits and flags per tenant](https://raw.githubusercontent.com/packstub/art/main/filament-features/banner.jpg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/packstub/filament-features.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-features)
[![Tests](https://img.shields.io/github/actions/workflow/status/packstub/filament-features/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/packstub/filament-features/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/packstub/filament-features.svg?style=flat-square)](https://packagist.org/packages/packstub/filament-features)
[![License](https://img.shields.io/packagist/l/packstub/filament-features.svg?style=flat-square)](https://github.com/packstub/filament-features/blob/main/LICENSE.md)
[![Sponsor](https://img.shields.io/badge/sponsor-%E2%9D%A4-ea4aaa?style=flat-square&logo=githubsponsors&logoColor=white)](https://github.com/sponsors/icaliman)

</div>

Feature flags and entitlements for Filament v5 tenants: define features and plans in code, store per-tenant overrides and global kill switches, check limits against real usage, and gate pages, actions, routes and Blade with one call.

![Switching from a Pro team to a Free team: the same Reports page shows an upgrade card, and Upgrade opens the plan page with its limits and usage](https://raw.githubusercontent.com/packstub/art/main/filament-features/docs/features.gif)

## Features

- **[Features and plans in code](#installation)**: on/off features and numeric limits, bundled into plans; the plan comes from any source.
- **[One call to check](#checking)**: whether a feature is on, its limit and what's left against real usage.
- **[Gating everywhere](#checking)**: routes, actions, form fields, pages and Blade, with an upgrade card when something is locked.
- **[Overrides](#overrides)**: trials, beta access and custom limits per tenant, global kill switches, each with an expiry and a reason.
- **[Plan and admin pages](#pages)**: "Your plan" with usage bars for tenants, flags and overrides for operators.
- **[Works with Filament Teams](#with-filament-teams)**: seat limits straight from the plan.
- **[Caching, commands and events](#configuration)**: a per-request memo with an optional cache store, list and prune commands, events when a limit is hit.

## Compatibility

| Plugin | Filament | Laravel | PHP |
| --- | --- | --- | --- |
| 1.x | 5.x | 12.x, 13.x | 8.3+ |

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
- `FeatureFlags` and `FeatureOverrides` (central/admin panel) — `->adminPages()`. Open to whoever can access a panel without tenancy; on a tenant panel they stay hidden unless `->canManageUsing(fn (User $user) => ...)` (or a `manageFeatures` Gate ability) says otherwise.

## With Filament Teams

```php
TeamsPlugin::make()->seatLimit(fn (Team $team) => Features::limit('seats', $team));
```

## Configuration

See `config/packstub-features.php`: `scope_model`, `features`, `plans`, `default_plan`, `overrides_table`, `connection`, `cache.store` / `ttl`, `upgrade_url`. Anything can be overridden per panel with `->config([...])`.

Commands: `features:list` prints every feature with its resolved value for a tenant, `features:prune` deletes expired overrides.

## Documentation

- [Installation](https://packstub.dev/docs/filament-features/installation)
- [Checking features](https://packstub.dev/docs/filament-features/checking)
- [Overrides and flags](https://packstub.dev/docs/filament-features/overrides)
- [Configuration](https://packstub.dev/docs/filament-features/configuration)

The same pages live in the [`docs/`](https://github.com/packstub/filament-features/tree/main/docs) directory of this repository.

## Testing

```bash
composer test
```

## Changelog

See the [changelog](https://github.com/packstub/filament-features/blob/main/CHANGELOG.md).

## Contributing

See [CONTRIBUTING](https://github.com/packstub/filament-features/blob/main/.github/CONTRIBUTING.md).

## Security

See [our security policy](https://github.com/packstub/filament-features/security/policy).

## Credits

- [Ion Caliman](https://github.com/icaliman) and [Packstub](https://packstub.dev)
- [All contributors](https://github.com/packstub/filament-features/graphs/contributors)

## License

The MIT License (MIT). See [LICENSE](https://github.com/packstub/filament-features/blob/main/LICENSE.md).
