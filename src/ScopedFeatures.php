<?php

namespace Packstub\Features;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Packstub\Features\Models\FeatureOverride;

/**
 * `Features::for($team)->active('api')` — the service bound to one scope.
 */
final class ScopedFeatures
{
    public function __construct(protected Features $features, protected ?Model $scope) {}

    public function scope(): ?Model
    {
        return $this->scope;
    }

    public function plan(): ?string
    {
        return $this->features->plan($this->scope);
    }

    public function planLabel(): ?string
    {
        return $this->features->planLabel($this->scope);
    }

    public function value(string $feature): bool|int|null
    {
        return $this->features->value($feature, $this->scope);
    }

    public function active(string $feature): bool
    {
        return $this->features->active($feature, $this->scope);
    }

    public function inactive(string $feature): bool
    {
        return $this->features->inactive($feature, $this->scope);
    }

    public function limit(string $feature): ?int
    {
        return $this->features->limit($feature, $this->scope);
    }

    public function used(string $feature): int
    {
        return $this->features->used($feature, $this->scope);
    }

    public function remaining(string $feature): ?int
    {
        return $this->features->remaining($feature, $this->scope);
    }

    public function canUse(string $feature, int $amount = 1): bool
    {
        return $this->features->canUse($feature, $this->scope, $amount);
    }

    public function assertCanUse(string $feature, int $amount = 1): void
    {
        $this->features->assertCanUse($feature, $this->scope, $amount);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function snapshot(): array
    {
        return $this->features->snapshot($this->scope);
    }

    public function override(string $feature, mixed $value, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        return $this->features->override($this->scope, $feature, $value, $expiresAt, $reason);
    }

    public function enable(string $feature, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        return $this->features->enable($this->scope, $feature, $expiresAt, $reason);
    }

    public function disable(string $feature, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        return $this->features->disable($this->scope, $feature, $expiresAt, $reason);
    }

    public function clearOverride(string $feature): void
    {
        $this->features->clearOverride($this->scope, $feature);
    }

    public function upgradeUrl(): ?string
    {
        return $this->features->upgradeUrl($this->scope);
    }
}
