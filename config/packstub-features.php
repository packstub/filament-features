<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scope model
    |--------------------------------------------------------------------------
    |
    | The model features are resolved for — usually the Filament tenant
    | (team, organization, workspace). When no scope is given, the current
    | Filament tenant is used.
    |
    */

    'scope_model' => env('PACKSTUB_FEATURES_SCOPE_MODEL', 'App\\Models\\Team'),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Every feature your app gates on. `type` is `boolean` (on/off) or `limit`
    | (a number; null = unlimited). `default` applies when no plan or override
    | says otherwise. Define them here or with FeaturesPlugin::features().
    |
    */

    'features' => [
        // 'api_access' => ['label' => 'API access', 'type' => 'boolean', 'default' => false, 'group' => 'Integrations'],
        // 'projects' => ['label' => 'Projects', 'type' => 'limit', 'default' => 3, 'unit' => 'projects', 'group' => 'Workspace'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Named bundles of feature values. Which plan a scope is on comes from
    | FeaturesPlugin::planUsing(fn (Model $scope) => ...) — a column, a
    | subscription, anything — falling back to `default_plan`.
    |
    */

    'plans' => [
        // 'free' => ['label' => 'Free', 'features' => ['projects' => 3]],
        // 'pro'  => ['label' => 'Pro',  'features' => ['projects' => 50, 'api_access' => true]],
    ],

    'default_plan' => env('PACKSTUB_FEATURES_DEFAULT_PLAN', 'free'),

    /*
    |--------------------------------------------------------------------------
    | Overrides
    |--------------------------------------------------------------------------
    |
    | Per-scope overrides (trials, exceptions, beta access) and global kill
    | switches (scope = null) are stored here. Resolution order:
    | scope override → plan → global override → feature default.
    |
    */

    'overrides_table' => 'feature_overrides',

    'connection' => env('PACKSTUB_FEATURES_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Resolved values are memoised per request. Set a store to also cache
    | overrides across requests (invalidated whenever an override changes).
    |
    */

    'cache' => [
        'store' => env('PACKSTUB_FEATURES_CACHE_STORE'),
        'ttl' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade
    |--------------------------------------------------------------------------
    |
    | Where locked features send people. A URL, or a closure via
    | FeaturesPlugin::upgradeUrl(). Null hides the call to action.
    |
    */

    'upgrade_url' => env('PACKSTUB_FEATURES_UPGRADE_URL'),

];
