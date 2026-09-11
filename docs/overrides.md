# Overrides and flags

Overrides are the only thing stored in the database. A per-scope override wins over the plan; a global override (no scope) applies to every scope whose plan is silent on the feature.

```php
Features::enable($team, 'reports', now()->addDays(14), 'trial');
Features::override($team, 'projects', 100, reason: 'enterprise deal');
Features::override($team, 'projects', null);          // unlimited
Features::disable($team, 'api_access', reason: 'abuse');
Features::clearOverride($team, 'reports');

Features::disable(null, 'reports');                    // kill switch for everyone
Features::enable(null, 'new_editor', now()->addWeek()); // global rollout
```

Expired overrides stop applying immediately and are deleted by `features:prune`.

## Admin pages

<img src="https://raw.githubusercontent.com/packstub/art/main/filament-features/docs/feature-flags.png" alt="Feature flags page with defaults, plan values and global overrides">

**Feature flags** lists every feature with its default, each plan's value and the global override, with *Enable globally* (a limit value for limit features), *Disable globally* and *Clear*.

<img src="https://raw.githubusercontent.com/packstub/art/main/filament-features/docs/feature-overrides.png" alt="Feature overrides page">

**Feature overrides** is a table of per-scope overrides with create, edit and remove — scope picker, feature, on/off or limit (or unlimited), reason and expiry.

## Caching

Resolved values are memoised per request. Set `cache.store` to also cache overrides across requests; every change through the service invalidates the affected scope. Changing the database by hand? Call `Features::flush($team)` or `Features::flush(all: true)`.

## Events

- `FeatureOverridden(?scope, feature, value)`
- `FeatureOverrideCleared(?scope, feature)`
- `FeatureLimitExceeded(?scope, feature, limit, used)`
