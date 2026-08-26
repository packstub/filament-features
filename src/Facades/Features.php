<?php

namespace Packstub\Features\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Packstub\Features\Features
 */
class Features extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Packstub\Features\Features::class;
    }
}
