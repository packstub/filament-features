<?php

namespace Packstub\Features;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Packstub\Features\Filament\Pages\FeatureFlags;
use Packstub\Features\Filament\Pages\FeatureOverrides;
use Packstub\Features\Filament\Pages\Plan;

class FeaturesPlugin implements Plugin
{
    public const ID = 'packstub-features';

    /** @var array<string, mixed> */
    protected array $features = [];

    /** @var array<string, mixed> */
    protected array $plans = [];

    protected ?Closure $planUsing = null;

    /** @var array<string, Closure> */
    protected array $usage = [];

    protected string|Closure|null $upgradeUrl = null;

    /** @var class-string<Plan>|null */
    protected ?string $planPage = Plan::class;

    /** @var class-string<FeatureFlags>|null */
    protected ?string $flagsPage = null;

    /** @var class-string<FeatureOverrides>|null */
    protected ?string $overridesPage = null;

    protected string|Closure|null $navigationGroup = null;

    protected ?int $navigationSort = null;

    /** @var array<string, mixed> */
    protected array $configOverrides = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return static::ID;
    }

    /**
     * @param  array<string, array<string, mixed>|string>  $features
     */
    public function features(array $features): static
    {
        $this->features = [...$this->features, ...$features];

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>|string>  $plans
     */
    public function plans(array $plans, ?string $default = null): static
    {
        $this->plans = [...$this->plans, ...$plans];

        if ($default !== null) {
            $this->configOverrides['default_plan'] = $default;
        }

        return $this;
    }

    /**
     * fn (?Model $scope): string|Plan|null — which plan a scope is on.
     */
    public function planUsing(?Closure $callback): static
    {
        $this->planUsing = $callback;

        return $this;
    }

    /**
     * fn (?Model $scope): int — current usage of a limit feature.
     */
    public function usage(string $feature, Closure $callback): static
    {
        $this->usage[$feature] = $callback;

        return $this;
    }

    public function upgradeUrl(string|Closure|null $url): static
    {
        $this->upgradeUrl = $url;

        return $this;
    }

    /**
     * The tenant-facing "Your plan" page (null to skip).
     *
     * @param  class-string<Plan>|null  $page
     */
    public function planPage(?string $page): static
    {
        $this->planPage = $page;

        return $this;
    }

    /**
     * Register the admin pages — feature flags (global kill switches) and
     * per-scope overrides. Meant for a central/admin panel.
     *
     * @param  class-string<FeatureFlags>|null  $flags
     * @param  class-string<FeatureOverrides>|null  $overrides
     */
    public function adminPages(?string $flags = FeatureFlags::class, ?string $overrides = FeatureOverrides::class): static
    {
        $this->flagsPage = $flags;
        $this->overridesPage = $overrides;

        return $this;
    }

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides  any packstub-features.* key
     */
    public function config(array $overrides): static
    {
        $this->configOverrides = [...$this->configOverrides, ...$overrides];

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        $group = value($this->navigationGroup);

        return $group === null ? null : (string) $group;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function register(Panel $panel): void
    {
        foreach ($this->configOverrides as $key => $value) {
            config()->set("packstub-features.{$key}", $value);
        }

        $features = app(Features::class);

        if ($this->features !== []) {
            $features->define($this->features);
        }

        if ($this->plans !== []) {
            $features->definePlans($this->plans);
        }

        if ($this->planUsing) {
            $features->planUsing($this->planUsing);
        }

        foreach ($this->usage as $feature => $callback) {
            $features->usageUsing($feature, $callback);
        }

        if ($this->upgradeUrl !== null) {
            $features->upgradeUrlUsing($this->upgradeUrl);
        }

        $panel->pages(array_values(array_filter([$this->planPage, $this->flagsPage, $this->overridesPage])));
    }

    public function boot(Panel $panel): void {}
}
