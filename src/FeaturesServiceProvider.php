<?php

namespace Packstub\Features;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Blade;
use Packstub\Features\Commands\ListFeaturesCommand;
use Packstub\Features\Commands\PruneOverridesCommand;
use Packstub\Features\Http\Middleware\RequireFeature;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FeaturesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'packstub-features';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$name)
            ->hasTranslations()
            ->hasMigration('create_feature_overrides_table')
            ->hasCommands([ListFeaturesCommand::class, PruneOverridesCommand::class])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->publishConfigFile()->publishMigrations()->askToRunMigrations();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Features::class);
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware('feature', RequireFeature::class);

        // @feature('api_access') … @else … @endfeature
        Blade::if('feature', fn (string $feature, $scope = null): bool => app(Features::class)->active($feature, $scope));

        // ->requiresFeature('api_access') on any action or schema component:
        // hidden unless the feature is active for the current tenant.
        $gate = function (string $feature, ?\Closure $scope = null) {
            /** @var Action|Component $this */
            return $this->visible(fn (): bool => app(Features::class)->active($feature, $scope ? $scope() : null));
        };

        Action::macro('requiresFeature', $gate);
        Component::macro('requiresFeature', $gate);
    }
}
