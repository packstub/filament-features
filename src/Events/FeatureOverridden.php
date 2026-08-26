<?php

namespace Packstub\Features\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class FeatureOverridden
{
    use Dispatchable;

    public function __construct(public readonly ?Model $scope, public readonly string $feature, public readonly mixed $value) {}
}
