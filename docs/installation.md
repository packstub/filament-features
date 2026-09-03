# Installation

## Requirements

| Package | Filament | Laravel | PHP |
|---|---|---|---|
| `1.x` | `^5.0` | `^12.0` / `^13.0` | `8.3+` |

## Purchasing a license

Licenses are sold at [packstub.dev/plugins/filament-features](https://packstub.dev/plugins/filament-features). After purchase, create an access token on the **Install Guide** page of your Packstub dashboard.

## Installing with Composer

```bash
composer config repositories.packstub-filament-features composer https://packstub.dev/composer/filament-features
composer config --auth http-basic.packstub.dev pkg_xxxxxxxxxxxxxxxx your-token-secret
composer require packstub/filament-features
php artisan packstub-features:install
```

The installer publishes `config/packstub-features.php` and the `feature_overrides` migration (string scope ids, so integer, UUID and Tenancy string keys all fit).

## Defining features and plans

```php
use Packstub\Features\FeaturesPlugin;

$panel->plugin(
    FeaturesPlugin::make()
        ->features([
            'api_access' => ['label' => 'API access', 'type' => 'boolean', 'default' => false, 'group' => 'Integrations', 'description' => 'Personal access tokens and webhooks.'],
            'reports'    => ['label' => 'Reports', 'default' => false, 'group' => 'Insights'],
            'projects'   => ['label' => 'Projects', 'type' => 'limit', 'default' => 3, 'unit' => 'projects', 'group' => 'Workspace'],
            'storage_gb' => ['label' => 'Storage', 'type' => 'limit', 'default' => 1, 'unit' => 'GB'],
        ])
        ->plans([
            'free'       => ['label' => 'Free', 'features' => ['projects' => 3]],
            'pro'        => ['label' => 'Pro', 'price' => '$29/mo', 'features' => ['projects' => 50, 'api_access' => true, 'reports' => true]],
            'enterprise' => ['label' => 'Enterprise', 'features' => ['projects' => null, 'api_access' => true, 'reports' => true, 'storage_gb' => 500]],
        ], default: 'free')
        ->planUsing(fn (?Team $team): ?string => $team?->plan)
        ->usage('projects', fn (?Team $team): int => $team?->projects()->count() ?? 0)
        ->upgradeUrl(fn (?Team $team): string => route('billing.show', $team))
        ->navigationGroup('Account'),
);
```

The same definitions can live in `config/packstub-features.php` instead — the plugin merges both.

## The tenant model

```php
use Packstub\Features\Concerns\HasFeatures;

class Team extends Model
{
    use HasFeatures; // hasFeature(), featureValue(), featureLimit(), featureRemaining(), canUseFeature(), featureOverrides(), features()
}
```

Optional — every check is also available on the `Features` facade with an explicit scope, and without one the current Filament tenant is used.

## Admin pages

`FeatureFlags` (global kill switches and rollouts) and `FeatureOverrides` (trials, beta access, negotiated limits) are operator pages. Register them on your central/admin panel, with the same feature and plan definitions as the tenant panel:

```php
$panel->plugin(FeaturesPlugin::make()->features([...])->plans([...])->planPage(null)->adminPages());
```

On a panel without tenancy they are open to everyone who can access the panel. On a tenant panel they stay hidden (navigation and route) unless you say who may open them:

```php
->adminPages()->canManageUsing(fn (User $user): bool => $user->isOperator())
```

Defining a `manageFeatures` Gate ability works too and applies on every panel.

## Scheduling

```php
Schedule::command('features:prune')->daily();   // drop expired overrides
```
