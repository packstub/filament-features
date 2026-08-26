<?php

namespace Packstub\Features\Exceptions;

class FeatureInactive extends FeaturesException
{
    public function __construct(public readonly string $feature, ?string $message = null)
    {
        parent::__construct($message ?? __('packstub-features::features.errors.inactive', ['feature' => $feature]));
    }
}
