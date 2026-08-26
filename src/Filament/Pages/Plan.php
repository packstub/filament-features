<?php

namespace Packstub\Features\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Packstub\Features\Facades\Features;
use Packstub\Features\FeaturesPlugin;

/**
 * "Your plan": what the current tenant is entitled to, with usage and an
 * upgrade call to action.
 */
class Plan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $slug = 'plan';

    protected string $view = 'packstub-features::pages.plan';

    public static function getNavigationLabel(): string
    {
        return __('packstub-features::features.plan.title');
    }

    public function getTitle(): string
    {
        return __('packstub-features::features.plan.title');
    }

    public function getSubheading(): ?string
    {
        $label = Features::planLabel();

        return $label === null ? null : __('packstub-features::features.plan.subheading', ['plan' => $label]);
    }

    public static function getNavigationGroup(): ?string
    {
        return FeaturesPlugin::get()->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FeaturesPlugin::get()->getNavigationSort() ?? parent::getNavigationSort();
    }

    public static function canAccess(): bool
    {
        return parent::canAccess() && Filament::getTenant() !== null;
    }

    protected function getHeaderActions(): array
    {
        $url = Features::upgradeUrl();

        return $url ? [
            Action::make('upgrade')
                ->label(__('packstub-features::features.plan.upgrade'))
                ->icon(Heroicon::OutlinedArrowUpCircle)
                ->url($url),
        ] : [];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>> group => rows
     */
    public function getGroups(): array
    {
        $groups = [];

        foreach (Features::snapshot() as $key => $row) {
            $groups[$row['feature']->group ?? __('packstub-features::features.plan.default_group')][] = $row + ['key' => $key];
        }

        return $groups;
    }
}
