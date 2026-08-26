<?php

namespace Packstub\Features\Commands;

use Illuminate\Console\Command;
use Packstub\Features\Features;

class ListFeaturesCommand extends Command
{
    protected $signature = 'features:list {scope? : Scope model key to resolve for}';

    protected $description = 'List features with defaults, plans and the resolved value for a scope';

    public function handle(Features $features): int
    {
        $scope = null;

        if ($key = $this->argument('scope')) {
            $model = config('packstub-features.scope_model');
            $scope = $model::query()->findOrFail($key);
            $this->components->info("Resolved for {$model} [{$key}] on plan [{$features->plan($scope)}]");
        }

        $rows = [];

        foreach ($features->features() as $feature) {
            $row = [$feature->key, $feature->type, $feature->format($feature->cast($feature->default))];

            foreach ($features->plans() as $plan) {
                $row[] = $plan->has($feature->key) ? $feature->format($feature->cast($plan->value($feature->key))) : '—';
            }

            if ($scope) {
                $row[] = $feature->format($features->value($feature->key, $scope)).' ('.$features->source($feature->key, $scope).')';
            }

            $rows[] = $row;
        }

        $headers = ['Feature', 'Type', 'Default', ...array_map(fn ($p) => $p->label, $features->plans()), ...($scope ? ['Resolved'] : [])];

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
