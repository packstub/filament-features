<?php

use Illuminate\Support\Facades\Event;
use Packstub\Features\Events\FeatureLimitExceeded;
use Packstub\Features\Events\FeatureOverridden;
use Packstub\Features\Events\FeatureOverrideCleared;
use Packstub\Features\Exceptions\FeatureInactive;
use Packstub\Features\Exceptions\FeatureLimitReached;
use Packstub\Features\Exceptions\UnknownFeature;
use Packstub\Features\Facades\Features;
use Packstub\Features\Models\FeatureOverride;
use Packstub\Features\Tests\Fixtures\Project;

it('registers features and plans from the plugin', function () {
    expect(array_keys(Features::features()))->toBe(['api_access', 'reports', 'projects', 'storage_gb'])
        ->and(Features::feature('projects')->isLimit())->toBeTrue()
        ->and(Features::feature('api_access')->isBoolean())->toBeTrue()
        ->and(Features::feature('reports')->type)->toBe('boolean')
        ->and(array_keys(Features::plans()))->toBe(['free', 'pro', 'enterprise'])
        ->and(Features::planDefinition('pro')->price)->toBe('$29')
        ->and(fn () => Features::feature('nope'))->toThrow(UnknownFeature::class);
});

it('resolves plan from the model, falling back to the default plan', function () {
    expect(Features::plan(createTeam()))->toBe('free')
        ->and(Features::plan(createTeam(plan: 'pro')))->toBe('pro')
        ->and(Features::planLabel(createTeam(plan: 'enterprise')))->toBe('Enterprise');

    Features::planUsing(fn ($team) => $team?->name === 'VIP' ? 'enterprise' : 'free');
    expect(Features::plan(createTeam('VIP', plan: 'pro')))->toBe('enterprise');
    Features::planUsing(null);
});

it('resolves values in order: override, plan, global, default', function () {
    $free = createTeam(plan: 'free');
    $pro = createTeam(plan: 'pro');

    expect(Features::value('api_access', $free))->toBeFalse()
        ->and(Features::source('api_access', $free))->toBe('default')
        ->and(Features::value('api_access', $pro))->toBeTrue()
        ->and(Features::source('api_access', $pro))->toBe('plan')
        ->and(Features::value('projects', $free))->toBe(3)
        ->and(Features::value('projects', $pro))->toBe(50)
        ->and(Features::value('projects', createTeam(plan: 'enterprise')))->toBeNull()
        ->and(Features::value('storage_gb', $pro))->toBe(1);

    // Global override applies where the plan is silent.
    Features::enable(null, 'api_access');
    expect(Features::value('api_access', $free))->toBeTrue()
        ->and(Features::source('api_access', $free))->toBe('global');

    // A scope override beats everything.
    Features::disable($free, 'api_access', reason: 'abuse');
    expect(Features::value('api_access', $free))->toBeFalse()
        ->and(Features::source('api_access', $free))->toBe('override')
        ->and(Features::value('api_access', $pro))->toBeTrue();

    Features::override($pro, 'projects', 5);
    expect(Features::limit('projects', $pro))->toBe(5);

    Features::clearOverride($pro, 'projects');
    expect(Features::limit('projects', $pro))->toBe(50);
});

it('treats limits as active unless zero and answers limit/remaining/canUse', function () {
    $team = createTeam(plan: 'free');

    expect(Features::active('projects', $team))->toBeTrue()
        ->and(Features::limit('projects', $team))->toBe(3)
        ->and(Features::used('projects', $team))->toBe(0)
        ->and(Features::remaining('projects', $team))->toBe(3)
        ->and(Features::canUse('projects', $team))->toBeTrue()
        ->and(Features::canUse('projects', $team, 4))->toBeFalse();

    Project::create(['team_id' => $team->id, 'name' => 'A']);
    Project::create(['team_id' => $team->id, 'name' => 'B']);
    Project::create(['team_id' => $team->id, 'name' => 'C']);

    expect(Features::remaining('projects', $team))->toBe(0)
        ->and(Features::canUse('projects', $team))->toBeFalse()
        ->and($team->canUseFeature('projects'))->toBeFalse()
        ->and($team->featureRemaining('projects'))->toBe(0);

    Event::fake([FeatureLimitExceeded::class]);
    expect(fn () => Features::assertCanUse('projects', $team))->toThrow(FeatureLimitReached::class);
    Event::assertDispatched(FeatureLimitExceeded::class, fn ($e) => $e->limit === 3 && $e->used === 3);

    Features::override($team, 'projects', 0);
    expect(Features::active('projects', $team))->toBeFalse()
        ->and(fn () => Features::assertCanUse('projects', $team))->toThrow(FeatureInactive::class);

    Features::override($team, 'projects', null);
    expect(Features::limit('projects', $team))->toBeNull()
        ->and(Features::remaining('projects', $team))->toBeNull()
        ->and(Features::canUse('projects', $team, 1000))->toBeTrue();

    // Boolean features report limits too.
    expect(Features::limit('api_access', $team))->toBe(0)
        ->and(Features::limit('api_access', createTeam(plan: 'pro')))->toBeNull()
        ->and(fn () => Features::assertCanUse('api_access', $team))->toThrow(FeatureInactive::class);
});

it('expires overrides and prunes them', function () {
    $team = createTeam();
    Features::enable($team, 'reports', now()->addDay(), 'trial');

    expect(Features::active('reports', $team))->toBeTrue();

    $this->travel(2)->days();
    Features::flush($team);

    expect(Features::active('reports', $team))->toBeFalse()
        ->and(FeatureOverride::count())->toBe(1);

    $this->artisan('features:prune')->assertSuccessful();
    expect(FeatureOverride::count())->toBe(0);
});

it('dispatches events and casts stored values', function () {
    Event::fake([FeatureOverridden::class, FeatureOverrideCleared::class]);
    $team = createTeam();

    $override = Features::override($team, 'storage_gb', '25', reason: 'negotiated');

    expect($override->scope->is($team))->toBeTrue()
        ->and($override->reason)->toBe('negotiated')
        ->and(Features::value('storage_gb', $team))->toBe(25)
        ->and($team->featureOverrides()->count())->toBe(1)
        ->and($team->features()->value('storage_gb'))->toBe(25)
        ->and(Features::for($team)->planLabel())->toBe('Free');

    Event::assertDispatched(FeatureOverridden::class, fn ($e) => $e->feature === 'storage_gb' && $e->value === '25');

    Features::for($team)->clearOverride('storage_gb');
    Event::assertDispatched(FeatureOverrideCleared::class);
    expect(Features::value('storage_gb', $team))->toBe(1);
});

it('builds a snapshot with usage and upgrade url', function () {
    $team = createTeam(plan: 'pro');
    Project::create(['team_id' => $team->id, 'name' => 'A']);

    $snapshot = Features::snapshot($team);

    expect($snapshot['projects'])->toMatchArray(['value' => 50, 'formatted' => '50 projects', 'active' => true, 'source' => 'plan', 'used' => 1, 'remaining' => 49])
        ->and($snapshot['api_access']['formatted'])->toBe('Included')
        ->and($snapshot['storage_gb'])->toMatchArray(['value' => 1, 'formatted' => '1 GB', 'used' => null])
        ->and(Features::upgradeUrl($team))->toBe("/billing/{$team->slug}");
});

it('caches overrides across requests when a store is configured and invalidates on change', function () {
    config()->set('packstub-features.cache.store', 'array');
    $team = createTeam();

    expect(Features::active('reports', $team))->toBeFalse();
    Features::enable($team, 'reports');
    expect(Features::active('reports', $team))->toBeTrue();

    // A fresh service instance reads the cache, not the database.
    FeatureOverride::query()->delete();
    Features::flush(all: true);
    expect(Features::active('reports', $team))->toBeTrue();

    Features::clearOverride($team, 'reports');
    Features::flush(all: true);
    expect(Features::active('reports', $team))->toBeFalse();
});

it('lists features from the console', function () {
    $team = createTeam(plan: 'pro');

    $this->artisan('features:list', ['scope' => $team->id])->assertSuccessful()
        ->expectsOutputToContain('50 projects (plan)');
});
