<?php

namespace Packstub\Features\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class FeatureOverrideCleared
{
    use Dispatchable;

    public function __construct(public readonly ?Model $scope, public readonly string $feature) {}
}
