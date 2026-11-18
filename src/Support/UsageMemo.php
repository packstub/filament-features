<?php

namespace Packstub\Features\Support;

/**
 * Usage counts already read in this request, keyed by scope and feature.
 *
 * Bound as a scoped instance, so the container drops it between Octane
 * requests and queued jobs: a long-lived worker never answers from another
 * request's count.
 */
class UsageMemo
{
    /** @var array<string, array<string, int>> scope key => feature => usage */
    protected array $usage = [];

    public function has(string $scope, string $feature): bool
    {
        return isset($this->usage[$scope][$feature]);
    }

    public function get(string $scope, string $feature): ?int
    {
        return $this->usage[$scope][$feature] ?? null;
    }

    public function put(string $scope, string $feature, int $used): int
    {
        return $this->usage[$scope][$feature] = $used;
    }

    /**
     * Forget one feature of a scope, or every feature of it.
     */
    public function forget(string $scope, ?string $feature = null): void
    {
        if ($feature === null) {
            unset($this->usage[$scope]);

            return;
        }

        unset($this->usage[$scope][$feature]);
    }

    /**
     * Forget a feature for every scope.
     */
    public function forgetFeature(string $feature): void
    {
        foreach (array_keys($this->usage) as $scope) {
            unset($this->usage[$scope][$feature]);
        }
    }

    public function flush(): void
    {
        $this->usage = [];
    }

    public function isEmpty(): bool
    {
        return $this->usage === [];
    }
}
