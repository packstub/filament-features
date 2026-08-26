<?php

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Packstub\Features\Facades\Features;
use Packstub\Features\Filament\Pages\FeatureFlags;
use Packstub\Features\Filament\Pages\FeatureOverrides;
use Packstub\Features\Filament\Pages\Plan;
use Packstub\Features\Models\FeatureOverride;
use Packstub\Features\Tests\Fixtures\Project;
use Packstub\Features\Tests\Fixtures\ReportsPage;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function onTeam($user, $team): void
{
    actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($team, isQuiet: true);
}

beforeEach(function () {
    $this->user = createUser();
    $this->free = createTeam('Free Co', 'free');
    $this->pro = createTeam('Pro Co', 'pro');
});

it('uses the current filament tenant as the default scope', function () {
    onTeam($this->user, $this->pro);
    expect(Features::active('api_access'))->toBeTrue()->and(Features::plan())->toBe('pro');

    Filament::setTenant($this->free, isQuiet: true);
    expect(Features::active('api_access'))->toBeFalse();
});

it('guards routes with the feature middleware', function () {
    $panel = Filament::getPanel('app');
    Route::get('/app/{tenant}/api', fn () => 'ok')->middleware([...$panel->getMiddleware(), 'auth', ...$panel->getTenantMiddleware(), 'feature:api_access'])->name('filament.app.api');
    Route::get('/app/{tenant}/api-redirect', fn () => 'ok')->middleware([...$panel->getMiddleware(), 'auth', ...$panel->getTenantMiddleware(), 'feature:api_access,redirect'])->name('filament.app.api2');

    actingAs($this->user)->get("/app/{$this->pro->slug}/api")->assertOk();
    $this->flushSession();
    actingAs($this->user)->get("/app/{$this->free->slug}/api")->assertForbidden();
    $this->flushSession();
    actingAs($this->user)->get("/app/{$this->free->slug}/api-redirect")->assertRedirect("/billing/{$this->free->slug}");
});

it('hides feature-gated pages from navigation and direct access', function () {
    actingAs($this->user)->get("/app/{$this->pro->slug}/reports")->assertOk()->assertSee('Reports here');
    $this->flushSession();
    actingAs($this->user)->get("/app/{$this->free->slug}/reports")->assertForbidden();

    onTeam($this->user, $this->free);
    expect(ReportsPage::shouldRegisterNavigation())->toBeFalse();
    Filament::setTenant($this->pro, isQuiet: true);
    expect(ReportsPage::shouldRegisterNavigation())->toBeTrue();
});

it('exposes a @feature directive and a locked component', function () {
    onTeam($this->user, $this->free);

    expect(Blade::render("@feature('api_access') on @else off @endfeature"))->toContain('off');
    expect(Blade::render("@feature('api_access', \$team) on @else off @endfeature", ['team' => $this->pro]))->toContain('on');

    $html = Blade::render('<x-packstub-features::locked feature="api_access" />');
    expect($html)->toContain('API access is not included in your plan')->toContain("/billing/{$this->free->slug}");

    Filament::setTenant($this->pro, isQuiet: true);
    expect(Blade::render('<x-packstub-features::locked feature="api_access" />'))->toBe('');
});

it('adds a requiresFeature macro to actions and components', function () {
    onTeam($this->user, $this->free);

    expect(Action::make('export')->requiresFeature('api_access')->isVisible())->toBeFalse()
        ->and(TextInput::make('token')->requiresFeature('api_access')->isVisible())->toBeFalse()
        ->and(Action::make('export')->requiresFeature('api_access', fn () => $this->pro)->isVisible())->toBeTrue();

    Filament::setTenant($this->pro, isQuiet: true);
    expect(Action::make('export')->requiresFeature('api_access')->isVisible())->toBeTrue();
});

it('renders the plan page with usage and an upgrade action', function () {
    Project::create(['team_id' => $this->free->id, 'name' => 'Only one']);

    actingAs($this->user)
        ->get("/app/{$this->free->slug}/plan")
        ->assertOk()
        ->assertSee("You're on the Free plan.")
        ->assertSee('1 of 3 projects used')
        ->assertSee('Not included')
        ->assertSee("/billing/{$this->free->slug}");

    onTeam($this->user, $this->pro);
    livewire(Plan::class)->assertSee("You're on the Pro plan.")->assertSee('50 projects')->assertSee('Included');
});

it('manages global flags from the admin page', function () {
    onTeam($this->user, $this->free);

    livewire(FeatureFlags::class)
        ->assertSee('API access')
        ->callAction('enable', arguments: ['feature' => 'api_access'])
        ->assertNotified('Saved');

    expect(Features::active('api_access', $this->free))->toBeTrue()
        ->and(FeatureOverride::global()->count())->toBe(1);

    livewire(FeatureFlags::class)
        ->callAction('enable', data: ['value' => 10], arguments: ['feature' => 'projects'])
        ->callAction('disable', arguments: ['feature' => 'reports'])
        ->callAction('clear', arguments: ['feature' => 'api_access']);

    expect(Features::value('projects', createTeam(plan: 'enterprise')))->toBeNull()   // plan wins over global
        ->and(Features::value('projects', $this->free))->toBe(3)                     // plan wins over global
        ->and(Features::value('storage_gb', $this->free))->toBe(1)
        ->and(Features::active('api_access', $this->free))->toBeFalse()
        ->and(FeatureOverride::global()->where('feature', 'projects')->value('value'))->toBe(10);
});

it('manages per-scope overrides from the admin page', function () {
    onTeam($this->user, $this->free);

    livewire(FeatureOverrides::class)
        ->callTableAction('create', data: ['scope' => $this->free->id, 'feature' => 'projects', 'limit' => 12, 'reason' => 'pilot'])
        ->assertNotified('Saved');

    expect(Features::limit('projects', $this->free))->toBe(12);

    $override = FeatureOverride::forScope($this->free)->firstOrFail();

    livewire(FeatureOverrides::class)
        ->assertCanSeeTableRecords([$override])
        ->assertSee('Free Co')
        ->callTableAction('edit', $override, data: ['unlimited' => true, 'reason' => 'pilot'])
        ->assertNotified('Saved');

    expect(Features::limit('projects', $this->free))->toBeNull();

    livewire(FeatureOverrides::class)
        ->callTableAction('create', data: ['scope' => $this->free->id, 'feature' => 'api_access', 'enabled' => true, 'expires_at' => now()->addWeek()])
        ->callTableAction('remove', $override)
        ->assertNotified('Override removed');

    expect(Features::limit('projects', $this->free))->toBe(3)
        ->and(Features::active('api_access', $this->free))->toBeTrue();
});
