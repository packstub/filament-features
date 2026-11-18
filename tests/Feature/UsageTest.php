<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Packstub\Features\Events\FeatureLimitExceeded;
use Packstub\Features\Exceptions\FeatureLimitReached;
use Packstub\Features\Facades\Features;
use Packstub\Features\Tests\Fixtures\Project;

/**
 * Count the queries $callback runs against the projects table.
 */
function countProjectQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $callback();
    } finally {
        DB::disableQueryLog();
    }

    return collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], '"projects"'))->count();
}

it('counts usage once when assertCanUse refuses', function () {
    Event::fake([FeatureLimitExceeded::class]);
    $team = createTeam(plan: 'free');

    foreach (['A', 'B', 'C'] as $name) {
        Project::create(['team_id' => $team->id, 'name' => $name]);
    }

    $queries = countProjectQueries(function () use ($team): void {
        expect(fn () => Features::assertCanUse('projects', $team))->toThrow(FeatureLimitReached::class);
    });

    expect($queries)->toBe(1);
    Event::assertDispatched(FeatureLimitExceeded::class, fn ($e) => $e->used === 3);
});

/**
 * Add a project the way another request or worker would: straight to the
 * table, so nothing in this process hears about it.
 */
function addProjectElsewhere(int $teamId, string $name): void
{
    DB::table('projects')->insert(['team_id' => $teamId, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
}

it('counts usage once per request for every read of the same feature', function () {
    $team = createTeam(plan: 'free');
    Project::create(['team_id' => $team->id, 'name' => 'A']);

    $queries = countProjectQueries(function () use ($team): void {
        expect(Features::used('projects', $team))->toBe(1)
            ->and(Features::remaining('projects', $team))->toBe(2)
            ->and(Features::canUse('projects', $team))->toBeTrue()
            ->and(Features::canUse('projects', $team, 3))->toBeFalse()
            ->and(Features::for($team)->used('projects'))->toBe(1)
            ->and($team->featureRemaining('projects'))->toBe(2)
            ->and(Features::snapshot($team)['projects'])->toMatchArray(['used' => 1, 'remaining' => 2]);
    });

    expect($queries)->toBe(1);
});

it('remembers usage per scope and feature', function () {
    $acme = createTeam(plan: 'free');
    $globex = createTeam('Globex', plan: 'free');
    Project::create(['team_id' => $acme->id, 'name' => 'A']);

    $queries = countProjectQueries(function () use ($acme, $globex): void {
        expect(Features::used('projects', $acme))->toBe(1)
            ->and(Features::used('projects', $globex))->toBe(0)
            ->and(Features::used('projects', $acme))->toBe(1)
            ->and(Features::used('projects', $globex))->toBe(0);
    });

    expect($queries)->toBe(2);
});

it('forgets remembered usage with forgetUsage and forgetAllUsage', function () {
    $acme = createTeam(plan: 'free');
    $globex = createTeam('Globex', plan: 'free');

    expect(Features::used('projects', $acme))->toBe(0)
        ->and(Features::used('projects', $globex))->toBe(0);

    addProjectElsewhere($acme->id, 'A');
    addProjectElsewhere($globex->id, 'B');

    expect(Features::used('projects', $acme))->toBe(0);

    Features::forgetUsage('projects', $acme);

    expect(Features::used('projects', $acme))->toBe(1)
        ->and(Features::used('projects', $globex))->toBe(0);

    // Every feature of a scope, through the scoped API.
    Features::for($globex)->forgetUsage();
    expect(Features::used('projects', $globex))->toBe(1);

    addProjectElsewhere($acme->id, 'C');
    addProjectElsewhere($globex->id, 'D');
    Features::forgetAllUsage();

    expect(Features::used('projects', $acme))->toBe(2)
        ->and(Features::used('projects', $globex))->toBe(2);
});

it('forgets usage of the current tenant when no scope is given', function () {
    $team = createTeam(plan: 'free');
    filament()->setTenant($team, isQuiet: true);

    expect(Features::used('projects'))->toBe(0);

    addProjectElsewhere($team->id, 'A');
    Features::forgetUsage('projects');

    expect(Features::used('projects'))->toBe(1);
});

it('counts again after a model is saved or deleted in the same request', function () {
    $team = createTeam(plan: 'free');

    expect(Features::remaining('projects', $team))->toBe(3);

    $project = Project::create(['team_id' => $team->id, 'name' => 'A']);
    expect(Features::remaining('projects', $team))->toBe(2);

    $project->delete();
    expect(Features::remaining('projects', $team))->toBe(3);
});

it('counts fresh when asked to', function () {
    $team = createTeam(plan: 'free');

    expect(Features::canUse('projects', $team, 3))->toBeTrue();

    addProjectElsewhere($team->id, 'A');

    expect(Features::canUse('projects', $team, 3))->toBeTrue()
        ->and(Features::canUse('projects', $team, 3, fresh: true))->toBeFalse()
        ->and(Features::for($team)->used('projects'))->toBe(1);

    addProjectElsewhere($team->id, 'B');

    expect(Features::used('projects', $team, fresh: true))->toBe(2)
        ->and(Features::for($team)->remaining('projects', fresh: true))->toBe(1)
        ->and($team->canUseFeature('projects', 2, fresh: true))->toBeFalse();
});

it('re-checks usage fresh under a lock and never goes over the limit', function () {
    Event::fake([FeatureLimitExceeded::class]);
    $team = createTeam(plan: 'free');
    addProjectElsewhere($team->id, 'A');
    addProjectElsewhere($team->id, 'B');

    // Two people opened the form while there was room for one more project.
    expect(Features::canUse('projects', $team))->toBeTrue();

    $save = fn (string $name): bool => Cache::lock("projects:{$team->id}", 10)->block(1, function () use ($team, $name): bool {
        if (! Features::canUse('projects', $team, fresh: true)) {
            return false;
        }

        addProjectElsewhere($team->id, $name);

        return true;
    });

    $saveAsserting = fn (string $name) => Cache::lock("projects:{$team->id}", 10)->block(1, function () use ($team, $name): void {
        Features::assertCanUse('projects', $team);

        addProjectElsewhere($team->id, $name);
    });

    expect($save('C'))->toBeTrue()
        // The remembered count still says 2: only the fresh re-check sees C.
        ->and(Features::canUse('projects', $team))->toBeTrue()
        ->and($save('D'))->toBeFalse()
        ->and(fn () => $saveAsserting('E'))->toThrow(FeatureLimitReached::class)
        ->and(DB::table('projects')->where('team_id', $team->id)->count())->toBe(3);

    Event::assertDispatched(FeatureLimitExceeded::class, fn ($e) => $e->limit === 3 && $e->used === 3);
});

it('keeps remembered usage inside one request or job', function () {
    $team = createTeam(plan: 'free');

    expect(Features::used('projects', $team))->toBe(0);

    addProjectElsewhere($team->id, 'A');
    expect(Features::used('projects', $team))->toBe(0);

    // What Octane does between requests and the queue worker between jobs.
    app()->forgetScopedInstances();

    expect(Features::used('projects', $team))->toBe(1);
});

it('forgets usage when the resolver changes or definitions reload', function () {
    $team = createTeam(plan: 'free');

    expect(Features::used('projects', $team))->toBe(0);

    Features::usageUsing('projects', fn (): int => 7);
    expect(Features::used('projects', $team))->toBe(7);

    Features::resetDefinitions()->usageUsing('projects', fn (): int => 9);
    expect(Features::used('projects', $team))->toBe(9);
});
