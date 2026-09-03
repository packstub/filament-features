<?php

use Packstub\Features\Facades\Features;
use Packstub\Features\FeaturesPlugin;

it('applies definitions per panel and restores keys another panel overrode', function () {
    $team = createTeam();

    $panelA = FeaturesPlugin::make()
        ->features(['zeta_a' => ['type' => 'limit', 'default' => 3]])
        ->plans(['gold' => ['features' => ['zeta_a' => 10]]], default: 'gold')
        ->config(['cache.ttl' => 1]);
    $panelB = FeaturesPlugin::make()
        ->features(['zeta_b' => ['type' => 'limit', 'default' => 2]])
        ->usage('zeta_b', fn (): int => 1);

    $panelA->configure();

    expect(Features::has('zeta_a'))->toBeTrue()
        ->and(Features::has('zeta_b'))->toBeFalse()
        ->and(config('packstub-features.default_plan'))->toBe('gold')
        ->and(config('packstub-features.cache.ttl'))->toBe(1);

    $panelB->configure();

    expect(Features::has('zeta_a'))->toBeFalse()
        ->and(Features::has('zeta_b'))->toBeTrue()
        ->and(Features::used('zeta_b', $team))->toBe(1)
        ->and(config('packstub-features.default_plan'))->toBe('free')
        ->and(config('packstub-features.cache.ttl'))->toBe(300);
});
