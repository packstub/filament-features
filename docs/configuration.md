# Configuration

| Key | Default | Purpose |
|---|---|---|
| `scope_model` | `App\Models\Team` | the tenant model (admin scope picker, `features:list`) |
| `features` | `[]` | feature definitions (`label`, `type` boolean/limit, `default`, `description`, `unit`, `group`) |
| `plans` | `[]` | plan definitions (`label`, `description`, `price`, `features`) |
| `default_plan` | `free` | plan when the resolver returns nothing |
| `overrides_table` | `feature_overrides` | |
| `connection` | `null` | central connection in database-per-tenant apps |
| `cache.store` / `cache.ttl` | `null` / 300 | cross-request override cache |
| `upgrade_url` | `null` | where locked features send people |

## The plugin API

```php
FeaturesPlugin::make()
    ->features([...])
    ->plans([...], default: 'free')
    ->planUsing(fn (?Model $scope) => ...)      // string, Plan or null
    ->usage('projects', fn (?Model $scope) => ...)
    ->upgradeUrl(fn (?Model $scope) => ...)     // or a string
    ->planPage(MyPlanPage::class)               // null to skip
    ->adminPages()                              // FeatureFlags + FeatureOverrides (central/admin panel)
    ->canManageUsing(fn (User $user) => ...)    // who may open them; see installation
    ->navigationGroup('Account')->navigationSort(90)
    ->config(['cache.store' => 'redis']);
```

### Several panels

Every panel keeps its own `FeaturesPlugin` definitions. Filament registers all panels at boot, so console commands and queued jobs see the panel registered last; when a panel is served, its features, plans, usage counters and config overrides are applied again for that request. One app can therefore give an admin panel and a customer panel different scope models and plans. Keys a panel leaves unset come from the config file, never from another panel. When a seeder or test switches panels with `Filament::setCurrentPanel()`, call `FeaturesPlugin::get()->configure()` afterwards so the runtime follows.

## Commands

- `features:list {scope?}` — features, defaults, plan values and the resolved value for a scope.
- `features:prune` — delete expired overrides.

## With Filament Teams

```php
TeamsPlugin::make()->seatLimit(fn (Team $team): ?int => Features::limit('seats', $team));
```

## With Filament Tenancy

Set `scope_model` to the Tenant model and `connection` to the central connection; overrides use string scope ids, so Tenancy's string keys work as they are.
