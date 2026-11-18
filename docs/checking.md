# Checking features

## Values

```php
Features::value('projects', $team);      // 50, null (unlimited) or 0; booleans return bool
Features::active('api_access', $team);   // limits are active unless 0
Features::limit('projects', $team);      // ?int
Features::used('projects', $team);       // from the usage resolver
Features::remaining('projects', $team);  // ?int
Features::canUse('projects', $team, 2);
Features::source('projects', $team);     // override | plan | global | default
Features::for($team)->snapshot();        // everything, formatted, with usage
```

Omit the scope and the current Filament tenant is used. Every method is also on `Features::for($team)` and on the `HasFeatures` trait.

### Usage is counted once per request

The usage resolver runs once per tenant and feature in a request (or a queued job). `used()`, `remaining()`, `canUse()` and the plan page read that number again instead of running the same `COUNT` query for every button, policy and badge on the page. It is counted again after any Eloquent model is saved or deleted, and the memory is dropped between Octane requests and queued jobs.

When usage changes without a model event (a query builder insert, another process), forget it yourself:

```php
Features::forgetUsage('projects', $team);  // one feature
Features::forgetUsage(scope: $team);       // every feature of the tenant
Features::forgetAllUsage();                // everything remembered in this request
```

## Enforcing

```php
public function store(Team $team): void
{
    Features::assertCanUse('projects', $team);   // FeatureLimitReached / FeatureInactive
    $team->projects()->create([...]);
}
```

`assertCanUse()` always counts usage fresh, since it guards a write. Two people saving at the same moment can still both pass the check, so take a lock per tenant around the check and the save, and ask `canUse()` for a fresh count inside it:

```php
Cache::lock("projects:{$team->getKey()}", 10)->block(3, function () use ($team, $data) {
    if (! Features::canUse('projects', $team, fresh: true)) {   // or Features::assertCanUse('projects', $team)
        throw ValidationException::withMessages(['name' => 'Your plan is full.']);
    }

    return $team->projects()->create($data);
});
```

Without `fresh: true`, `canUse()` would answer with the count read earlier in the request, before the lock was taken.

`FeatureLimitReached` carries `feature`, `limit` and `used`; `FeatureLimitExceeded` is dispatched at the same time so you can nudge the user to upgrade.

## Gating

**Routes**

```php
Route::get('/tokens', ...)->middleware('feature:api_access');           // 403
Route::get('/reports', ...)->middleware('feature:reports,redirect');    // redirect to the upgrade URL
```

**Pages and resources**

```php
use Packstub\Features\Filament\Concerns\RequiresFeature;

class ReportsPage extends Page
{
    use RequiresFeature;

    protected static string $feature = 'reports';   // hidden from navigation and 403 on direct access
}
```

**Actions and form components**

```php
Action::make('export')->requiresFeature('api_access');
TextInput::make('webhook_url')->requiresFeature('api_access');
Section::make('Integrations')->requiresFeature('api_access', fn () => $this->record->team);
```

**Blade**

```blade
@feature('reports')
    <a href="{{ route('reports') }}">Reports</a>
@else
    <x-packstub-features::locked feature="reports">Reports are part of the Pro plan.</x-packstub-features::locked>
@endfeature
```

<img src="https://raw.githubusercontent.com/packstub/art/main/filament-features/docs/locked.png" alt="Locked feature card with an upgrade button">

## The plan page

`Packstub\Features\Filament\Pages\Plan` (`/plan`) shows every feature by group, usage bars for limits with a resolver, "custom" markers for overrides, and an Upgrade button when an upgrade URL exists.

<img src="https://raw.githubusercontent.com/packstub/art/main/filament-features/docs/plan.png" alt="Your plan page with usage bars">
