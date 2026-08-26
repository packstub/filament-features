<?php

namespace Packstub\Features\Tests\Fixtures;

use Filament\Pages\Page;
use Packstub\Features\Filament\Concerns\RequiresFeature;

class ReportsPage extends Page
{
    use RequiresFeature;

    protected static string $feature = 'reports';

    protected static ?string $slug = 'reports';

    protected string $view = 'reports';
}
