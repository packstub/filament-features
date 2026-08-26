<?php

namespace Packstub\Features\Exceptions;

class UnknownFeature extends FeaturesException
{
    public static function named(string $feature): static
    {
        return new static("Unknown feature [{$feature}]. Define it in config/packstub-features.php or with FeaturesPlugin::features().");
    }
}
