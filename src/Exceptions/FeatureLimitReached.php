<?php

namespace Packstub\Features\Exceptions;

class FeatureLimitReached extends FeaturesException
{
    public function __construct(public readonly string $feature, public readonly int $limit, public readonly int $used, ?string $message = null)
    {
        parent::__construct($message ?? __('packstub-features::features.errors.limit_reached', ['feature' => $feature, 'limit' => $limit]));
    }
}
