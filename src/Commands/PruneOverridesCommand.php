<?php

namespace Packstub\Features\Commands;

use Illuminate\Console\Command;
use Packstub\Features\Features;

class PruneOverridesCommand extends Command
{
    protected $signature = 'features:prune';

    protected $description = 'Delete expired feature overrides';

    public function handle(Features $features): int
    {
        $this->components->info("Pruned {$features->pruneOverrides()} expired override(s).");

        return self::SUCCESS;
    }
}
