<?php

namespace Packstub\Features\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Packstub\Features\Facades\Features;
use Packstub\Features\FeaturesPlugin;
use Packstub\Features\Models\FeatureOverride;

/**
 * Admin: per-scope overrides — trials, beta access, negotiated limits.
 */
class FeatureOverrides extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $slug = 'feature-overrides';

    protected string $view = 'packstub-features::pages.feature-overrides';

    public static function getNavigationLabel(): string
    {
        return __('packstub-features::features.overrides.title');
    }

    public function getTitle(): string
    {
        return __('packstub-features::features.overrides.title');
    }

    public static function canAccess(): bool
    {
        return parent::canAccess() && FeaturesPlugin::get()->canManage();
    }

    public static function getNavigationGroup(): ?string
    {
        return FeaturesPlugin::get()->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => FeatureOverride::query()->whereNotNull('scope_type'))
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('packstub-features::features.overrides.empty'))
            ->columns([
                TextColumn::make('scope_id')
                    ->label(__('packstub-features::features.fields.scope'))
                    ->formatStateUsing(fn (FeatureOverride $record): string => static::scopeLabel($record->scope) ?? "{$record->scope_type} #{$record->scope_id}")
                    ->searchable(),
                TextColumn::make('feature')
                    ->label(__('packstub-features::features.fields.feature'))
                    ->formatStateUsing(fn (string $state): string => Features::has($state) ? Features::feature($state)->label : $state)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('value')
                    ->label(__('packstub-features::features.fields.value'))
                    ->formatStateUsing(fn (FeatureOverride $record): string => Features::has($record->feature) ? Features::feature($record->feature)->format(Features::feature($record->feature)->cast($record->value)) : json_encode($record->value)),
                TextColumn::make('reason')->label(__('packstub-features::features.fields.reason'))->placeholder('—')->limit(40),
                TextColumn::make('expires_at')
                    ->label(__('packstub-features::features.fields.expires'))
                    ->since()
                    ->placeholder(__('packstub-features::features.values.never'))
                    ->color(fn (FeatureOverride $record): ?string => $record->isExpired() ? 'danger' : null),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('packstub-features::features.overrides.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (FeatureOverride $record): array => [
                        'enabled' => Features::has($record->feature) ? Features::active($record->feature, $record->scope) : (bool) $record->value,
                        'limit' => is_int($record->value) ? $record->value : null,
                        'unlimited' => $record->value === null,
                        'reason' => $record->reason,
                        'expires_at' => $record->expires_at,
                    ])
                    ->schema(fn (FeatureOverride $record): array => $this->valueSchema($record->feature))
                    ->action(function (FeatureOverride $record, array $data): void {
                        Features::override($record->scope, $record->feature, $this->valueFromData($record->feature, $data), $this->expiry($data), $data['reason'] ?? null);

                        Notification::make()->title(__('packstub-features::features.notifications.saved'))->success()->send();
                    }),
                Action::make('remove')
                    ->label(__('packstub-features::features.overrides.remove'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (FeatureOverride $record): void {
                        Features::clearOverride($record->scope, $record->feature);

                        Notification::make()->title(__('packstub-features::features.notifications.removed'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('packstub-features::features.overrides.create'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalWidth('md')
                    ->schema(fn (): array => [
                        Select::make('scope')
                            ->label(__('packstub-features::features.fields.scope'))
                            ->options(fn (): array => static::scopeOptions())
                            ->searchable()
                            ->required(),
                        Select::make('feature')
                            ->label(__('packstub-features::features.fields.feature'))
                            ->options(collect(Features::features())->map(fn ($f) => $f->label)->all())
                            ->required()
                            ->live(),
                        Toggle::make('enabled')
                            ->label(__('packstub-features::features.fields.enabled'))
                            ->default(true)
                            ->visible(fn (Get $get): bool => filled($get('feature')) && Features::feature($get('feature'))->isBoolean()),
                        Toggle::make('unlimited')
                            ->label(__('packstub-features::features.values.unlimited'))
                            ->live()
                            ->visible(fn (Get $get): bool => filled($get('feature')) && Features::feature($get('feature'))->isLimit()),
                        TextInput::make('limit')
                            ->label(__('packstub-features::features.fields.limit'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => filled($get('feature')) && Features::feature($get('feature'))->isLimit() && ! $get('unlimited')),
                        TextInput::make('reason')->label(__('packstub-features::features.fields.reason'))->maxLength(255),
                        DateTimePicker::make('expires_at')->label(__('packstub-features::features.fields.expires'))->minDate(now()),
                    ])
                    ->action(function (array $data): void {
                        $model = config('packstub-features.scope_model');
                        $scope = $model::query()->findOrFail($data['scope']);

                        Features::override($scope, $data['feature'], $this->valueFromData($data['feature'], $data), $this->expiry($data), $data['reason'] ?? null);

                        Notification::make()->title(__('packstub-features::features.notifications.saved'))->success()->send();
                    }),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function valueSchema(string $feature): array
    {
        $definition = Features::feature($feature);

        return [
            ...($definition->isBoolean() ? [Toggle::make('enabled')->label(__('packstub-features::features.fields.enabled'))] : [
                Toggle::make('unlimited')->label(__('packstub-features::features.values.unlimited'))->live(),
                TextInput::make('limit')->label(__('packstub-features::features.fields.limit'))->numeric()->minValue(0)->visible(fn (Get $get): bool => ! $get('unlimited')),
            ]),
            TextInput::make('reason')->label(__('packstub-features::features.fields.reason'))->maxLength(255),
            DateTimePicker::make('expires_at')->label(__('packstub-features::features.fields.expires')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function valueFromData(string $feature, array $data): bool|int|null
    {
        $definition = Features::feature($feature);

        if ($definition->isBoolean()) {
            return (bool) ($data['enabled'] ?? false);
        }

        return ($data['unlimited'] ?? false) ? null : (int) ($data['limit'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function expiry(array $data): ?Carbon
    {
        return filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null;
    }

    /**
     * @return array<int|string, string>
     */
    public static function scopeOptions(): array
    {
        $model = config('packstub-features.scope_model');

        return $model::query()->limit(500)->get()->mapWithKeys(fn (Model $scope): array => [$scope->getKey() => static::scopeLabel($scope) ?? (string) $scope->getKey()])->all();
    }

    public static function scopeLabel(?Model $scope): ?string
    {
        if ($scope === null) {
            return null;
        }

        return $scope->getAttribute('name') ?? $scope->getAttribute('title') ?? null;
    }
}
