<?php

namespace Packstub\Features\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Packstub\Features\Facades\Features;
use Packstub\Features\FeaturesPlugin;
use Packstub\Features\Support\Feature;

/**
 * Admin: every feature with its default, plan values and global override
 * (kill switch / global rollout).
 */
class FeatureFlags extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $slug = 'feature-flags';

    protected string $view = 'packstub-features::pages.feature-flags';

    public static function getNavigationLabel(): string
    {
        return __('packstub-features::features.flags.title');
    }

    public function getTitle(): string
    {
        return __('packstub-features::features.flags.title');
    }

    public function getSubheading(): ?string
    {
        return __('packstub-features::features.flags.subheading');
    }

    public static function getNavigationGroup(): ?string
    {
        return FeaturesPlugin::get()->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(): array
    {
        $rows = [];

        foreach (Features::features() as $key => $feature) {
            $global = Features::overrideFor(null, $key);

            $rows[] = [
                'key' => $key,
                'feature' => $feature,
                'default' => $feature->format($feature->cast($feature->default)),
                'plans' => collect(Features::plans())->map(fn ($plan) => $plan->has($key) ? $feature->format($feature->cast($plan->value($key))) : '—')->all(),
                'global' => $global ? $feature->format($feature->cast($global->value)) : null,
                'global_expires' => $global?->expires_at,
            ];
        }

        return $rows;
    }

    public function enableAction(): Action
    {
        return Action::make('enable')
            ->label(__('packstub-features::features.flags.enable_globally'))
            ->icon(Heroicon::OutlinedBolt)
            ->color('success')
            ->size('sm')
            ->link()
            ->schema(fn (array $arguments): array => Features::feature($arguments['feature'])->isLimit() ? [
                TextInput::make('value')
                    ->label(__('packstub-features::features.fields.limit'))
                    ->helperText(__('packstub-features::features.fields.limit_help'))
                    ->numeric()
                    ->minValue(0),
            ] : [])
            ->action(function (array $arguments, array $data): void {
                $feature = Features::feature($arguments['feature']);

                $feature->isLimit() && filled($data['value'] ?? null)
                    ? Features::override(null, $feature->key, (int) $data['value'])
                    : Features::enable(null, $feature->key);

                Notification::make()->title(__('packstub-features::features.notifications.saved'))->success()->send();
            });
    }

    public function disableAction(): Action
    {
        return Action::make('disable')
            ->label(__('packstub-features::features.flags.disable_globally'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalDescription(__('packstub-features::features.flags.disable_confirm'))
            ->action(function (array $arguments): void {
                Features::disable(null, $arguments['feature'], reason: 'kill switch');

                Notification::make()->title(__('packstub-features::features.notifications.saved'))->success()->send();
            });
    }

    public function clearAction(): Action
    {
        return Action::make('clear')
            ->label(__('packstub-features::features.flags.clear'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->size('sm')
            ->link()
            ->action(function (array $arguments): void {
                Features::clearOverride(null, $arguments['feature']);

                Notification::make()->title(__('packstub-features::features.notifications.saved'))->success()->send();
            });
    }
}
