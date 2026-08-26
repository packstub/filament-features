<?php

namespace Packstub\Features\Filament\Concerns;

use Packstub\Features\Facades\Features;

/**
 * For Filament pages and resources: hide them (and 403 direct access)
 * unless `static::$feature` is active for the current tenant.
 *
 *   protected static string $feature = 'reports';
 */
trait RequiresFeature
{
    public static function featureIsActive(): bool
    {
        return Features::active(static::$feature);
    }

    public static function canAccess(): bool
    {
        return parent::canAccess() && static::featureIsActive();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation() && static::featureIsActive();
    }
}
