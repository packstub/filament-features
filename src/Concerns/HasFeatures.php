<?php

namespace Packstub\Features\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Packstub\Features\Facades\Features;
use Packstub\Features\Models\FeatureOverride;
use Packstub\Features\ScopedFeatures;

/**
 * For the scope (tenant) model.
 */
trait HasFeatures
{
    public function features(): ScopedFeatures
    {
        return Features::for($this);
    }

    public function featureOverrides(): MorphMany
    {
        return $this->morphMany(FeatureOverride::class, 'scope');
    }

    public function hasFeature(string $feature): bool
    {
        return Features::active($feature, $this);
    }

    public function featureValue(string $feature): bool|int|null
    {
        return Features::value($feature, $this);
    }

    public function featureLimit(string $feature): ?int
    {
        return Features::limit($feature, $this);
    }

    public function featureRemaining(string $feature, bool $fresh = false): ?int
    {
        return Features::remaining($feature, $this, $fresh);
    }

    public function canUseFeature(string $feature, int $amount = 1, bool $fresh = false): bool
    {
        return Features::canUse($feature, $this, $amount, $fresh);
    }

    public function featurePlan(): ?string
    {
        return Features::plan($this);
    }
}
