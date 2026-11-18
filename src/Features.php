<?php

namespace Packstub\Features;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Packstub\Features\Events\FeatureLimitExceeded;
use Packstub\Features\Events\FeatureOverridden;
use Packstub\Features\Events\FeatureOverrideCleared;
use Packstub\Features\Exceptions\FeatureInactive;
use Packstub\Features\Exceptions\FeatureLimitReached;
use Packstub\Features\Exceptions\UnknownFeature;
use Packstub\Features\Models\FeatureOverride;
use Packstub\Features\Support\Feature;
use Packstub\Features\Support\Plan;

/**
 * Resolves what a scope (tenant) is entitled to:
 * scope override → plan value → global override → feature default.
 */
class Features
{
    /** @var array<string, Feature> */
    protected array $features = [];

    /** @var array<string, Plan> */
    protected array $plans = [];

    protected ?Closure $planUsing = null;

    /** @var array<string, Closure> */
    protected array $usage = [];

    protected string|Closure|null $upgradeUrl = null;

    /** @var array<string, array<string, FeatureOverride>> memo: scope key => feature => override */
    protected array $overrideMemo = [];

    protected bool $definitionsLoaded = false;

    // ------------------------------------------------------------------
    // Definitions
    // ------------------------------------------------------------------

    /**
     * @param  array<string, array<string, mixed>|string>  $features
     */
    public function define(array $features): static
    {
        $this->loadDefinitions();

        foreach ($features as $key => $definition) {
            $this->features[$key] = $definition instanceof Feature ? $definition : Feature::fromArray($key, $definition);
        }

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>|string>  $plans
     */
    public function definePlans(array $plans): static
    {
        $this->loadDefinitions();

        foreach ($plans as $key => $definition) {
            $this->plans[$key] = $definition instanceof Plan ? $definition : Plan::fromArray($key, $definition);
        }

        return $this;
    }

    /**
     * Forget every runtime definition — features, plans, usage counters and
     * memoised overrides — so the next call reloads from config (and whatever
     * the current panel's plugin defines on top). `FeaturesPlugin::configure()`
     * calls this when a panel is registered or booted.
     */
    public function resetDefinitions(): static
    {
        $this->features = [];
        $this->plans = [];
        $this->usage = [];
        $this->overrideMemo = [];
        $this->definitionsLoaded = false;

        return $this;
    }

    protected function loadDefinitions(): void
    {
        if ($this->definitionsLoaded) {
            return;
        }

        $this->definitionsLoaded = true;

        foreach ((array) config('packstub-features.features', []) as $key => $definition) {
            $this->features[$key] ??= Feature::fromArray($key, $definition);
        }

        foreach ((array) config('packstub-features.plans', []) as $key => $definition) {
            $this->plans[$key] ??= Plan::fromArray($key, $definition);
        }
    }

    /**
     * @return array<string, Feature>
     */
    public function features(): array
    {
        $this->loadDefinitions();

        return $this->features;
    }

    public function feature(string $key): Feature
    {
        return $this->features()[$key] ?? throw UnknownFeature::named($key);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->features());
    }

    /**
     * @return array<string, Plan>
     */
    public function plans(): array
    {
        $this->loadDefinitions();

        return $this->plans;
    }

    public function planDefinition(string $key): ?Plan
    {
        return $this->plans()[$key] ?? null;
    }

    public function planUsing(?Closure $callback): static
    {
        $this->planUsing = $callback;

        return $this;
    }

    public function usageUsing(string $feature, Closure $callback): static
    {
        $this->usage[$feature] = $callback;

        return $this;
    }

    public function upgradeUrlUsing(string|Closure|null $url): static
    {
        $this->upgradeUrl = $url;

        return $this;
    }

    // ------------------------------------------------------------------
    // Scope & plan
    // ------------------------------------------------------------------

    public function currentScope(): ?Model
    {
        return Filament::getTenant();
    }

    public function for(?Model $scope): ScopedFeatures
    {
        return new ScopedFeatures($this, $scope ?? $this->currentScope());
    }

    /**
     * The plan key a scope is on: the planUsing() callback, a `plan`
     * attribute on the model, or the default plan.
     */
    public function plan(?Model $scope = null): ?string
    {
        $scope ??= $this->currentScope();

        $plan = null;

        if ($this->planUsing) {
            $plan = ($this->planUsing)($scope);
        } elseif ($scope && $scope->getAttribute('plan') !== null) {
            $plan = $scope->getAttribute('plan');
        }

        $plan = $plan instanceof Plan ? $plan->key : ($plan === null ? null : (string) $plan);

        return $plan ?? config('packstub-features.default_plan');
    }

    public function planLabel(?Model $scope = null): ?string
    {
        $key = $this->plan($scope);

        return $key === null ? null : ($this->planDefinition($key)?->label ?? $key);
    }

    // ------------------------------------------------------------------
    // Resolution
    // ------------------------------------------------------------------

    /**
     * The resolved value: bool for boolean features, int|null (null =
     * unlimited) for limits.
     */
    public function value(string $feature, ?Model $scope = null): bool|int|null
    {
        $definition = $this->feature($feature);
        $scope ??= $this->currentScope();

        if ($scope && ($override = $this->overrideFor($scope, $feature))) {
            return $definition->cast($override->value);
        }

        $plan = $this->plan($scope);

        if ($plan !== null && ($planDefinition = $this->planDefinition($plan)) && $planDefinition->has($feature)) {
            return $definition->cast($planDefinition->value($feature));
        }

        if ($global = $this->overrideFor(null, $feature)) {
            return $definition->cast($global->value);
        }

        return $definition->cast($definition->default);
    }

    /**
     * Where the value came from: `override`, `plan`, `global` or `default`.
     */
    public function source(string $feature, ?Model $scope = null): string
    {
        $this->feature($feature);
        $scope ??= $this->currentScope();

        if ($scope && $this->overrideFor($scope, $feature)) {
            return 'override';
        }

        $plan = $this->plan($scope);

        if ($plan !== null && $this->planDefinition($plan)?->has($feature)) {
            return 'plan';
        }

        return $this->overrideFor(null, $feature) ? 'global' : 'default';
    }

    /**
     * Is the feature on? Limits are "on" unless they are 0.
     */
    public function active(string $feature, ?Model $scope = null): bool
    {
        $value = $this->value($feature, $scope);

        return $this->feature($feature)->isLimit() ? ($value === null || $value > 0) : (bool) $value;
    }

    public function inactive(string $feature, ?Model $scope = null): bool
    {
        return ! $this->active($feature, $scope);
    }

    /**
     * The limit of a limit feature; null = unlimited.
     */
    public function limit(string $feature, ?Model $scope = null): ?int
    {
        $definition = $this->feature($feature);
        $value = $this->value($feature, $scope);

        if ($definition->isBoolean()) {
            return $value ? null : 0;
        }

        return $value;
    }

    /**
     * Current usage from the registered usage resolver (0 without one).
     */
    public function used(string $feature, ?Model $scope = null): int
    {
        $scope ??= $this->currentScope();

        $resolver = $this->usage[$feature] ?? null;

        return $resolver ? (int) $resolver($scope) : 0;
    }

    /**
     * Remaining allowance; null = unlimited.
     */
    public function remaining(string $feature, ?Model $scope = null): ?int
    {
        $limit = $this->limit($feature, $scope);

        return $limit === null ? null : max(0, $limit - $this->used($feature, $scope));
    }

    /**
     * Can the scope consume $amount more of the feature right now?
     */
    public function canUse(string $feature, ?Model $scope = null, int $amount = 1): bool
    {
        if (! $this->active($feature, $scope)) {
            return false;
        }

        $remaining = $this->remaining($feature, $scope);

        return $remaining === null || $remaining >= $amount;
    }

    /**
     * Like canUse(), but throws FeatureInactive / FeatureLimitReached.
     */
    public function assertCanUse(string $feature, ?Model $scope = null, int $amount = 1): void
    {
        $scope ??= $this->currentScope();

        if (! $this->active($feature, $scope)) {
            throw new FeatureInactive($feature);
        }

        $limit = $this->limit($feature, $scope);

        if ($limit === null) {
            return;
        }

        $used = $this->used($feature, $scope);

        if ($used + $amount > $limit) {
            FeatureLimitExceeded::dispatch($scope, $feature, $limit, $used);

            throw new FeatureLimitReached($feature, $limit, $used);
        }
    }

    /**
     * Every feature resolved for a scope — for plan pages and API responses.
     *
     * @return array<string, array{feature: Feature, value: bool|int|null, formatted: string, active: bool, source: string, used: int|null, remaining: int|null}>
     */
    public function snapshot(?Model $scope = null): array
    {
        $scope ??= $this->currentScope();
        $out = [];

        foreach ($this->features() as $key => $feature) {
            $value = $this->value($key, $scope);

            $out[$key] = [
                'feature' => $feature,
                'value' => $value,
                'formatted' => $feature->format($value),
                'active' => $this->active($key, $scope),
                'source' => $this->source($key, $scope),
                'used' => $feature->isLimit() && isset($this->usage[$key]) ? $this->used($key, $scope) : null,
                'remaining' => $feature->isLimit() && isset($this->usage[$key]) ? $this->remaining($key, $scope) : null,
            ];
        }

        return $out;
    }

    public function upgradeUrl(?Model $scope = null): ?string
    {
        $scope ??= $this->currentScope();
        $url = $this->upgradeUrl ?? config('packstub-features.upgrade_url');

        if ($url instanceof Closure) {
            $url = $url($scope);
        }

        return $url === null || $url === '' ? null : (string) $url;
    }

    // ------------------------------------------------------------------
    // Overrides
    // ------------------------------------------------------------------

    /**
     * Set a per-scope override (or a global one with a null scope).
     */
    public function override(?Model $scope, string $feature, mixed $value, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        $this->feature($feature);

        $override = FeatureOverride::query()->updateOrCreate(
            [
                'scope_type' => $scope?->getMorphClass(),
                'scope_id' => $scope === null ? null : (string) $scope->getKey(),
                'feature' => $feature,
            ],
            ['value' => $value, 'expires_at' => $expiresAt, 'reason' => $reason],
        );

        $this->flush($scope);

        FeatureOverridden::dispatch($scope, $feature, $value);

        return $override;
    }

    public function enable(?Model $scope, string $feature, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        $definition = $this->feature($feature);

        return $this->override($scope, $feature, $definition->isLimit() ? null : true, $expiresAt, $reason);
    }

    public function disable(?Model $scope, string $feature, ?Carbon $expiresAt = null, ?string $reason = null): FeatureOverride
    {
        $definition = $this->feature($feature);

        return $this->override($scope, $feature, $definition->isLimit() ? 0 : false, $expiresAt, $reason);
    }

    public function clearOverride(?Model $scope, string $feature): void
    {
        $query = FeatureOverride::query()->where('feature', $feature);

        $scope === null ? $query->global() : $query->forScope($scope);

        $deleted = $query->delete() > 0;

        $this->flush($scope);

        if ($deleted) {
            FeatureOverrideCleared::dispatch($scope, $feature);
        }
    }

    /**
     * Active overrides for a scope (or global ones), keyed by feature.
     *
     * @return array<string, FeatureOverride>
     */
    public function overrides(?Model $scope): array
    {
        $key = $this->scopeKey($scope);

        if (array_key_exists($key, $this->overrideMemo)) {
            return $this->overrideMemo[$key];
        }

        $load = function () use ($scope): array {
            $query = FeatureOverride::query()->active();
            $scope === null ? $query->global() : $query->forScope($scope);

            return $query->get()->keyBy('feature')->all();
        };

        $store = config('packstub-features.cache.store');

        $overrides = $store
            ? Cache::store($store)->remember("packstub-features:{$key}", (int) config('packstub-features.cache.ttl', 300), $load)
            : $load();

        return $this->overrideMemo[$key] = $overrides;
    }

    public function overrideFor(?Model $scope, string $feature): ?FeatureOverride
    {
        return $this->overrides($scope)[$feature] ?? null;
    }

    /**
     * Forget memoised/cached overrides for a scope, or everything.
     */
    public function flush(?Model $scope = null, bool $all = false): void
    {
        if ($all) {
            $this->overrideMemo = [];

            return;
        }

        $key = $this->scopeKey($scope);
        unset($this->overrideMemo[$key]);

        if ($store = config('packstub-features.cache.store')) {
            Cache::store($store)->forget("packstub-features:{$key}");
        }
    }

    public function pruneOverrides(): int
    {
        $count = FeatureOverride::query()->whereNotNull('expires_at')->where('expires_at', '<', now())->delete();

        $this->flush(all: true);

        return $count;
    }

    protected function scopeKey(?Model $scope): string
    {
        return $scope === null ? 'global' : $scope->getMorphClass().':'.$scope->getKey();
    }
}
