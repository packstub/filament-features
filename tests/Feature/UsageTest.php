<?php

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
