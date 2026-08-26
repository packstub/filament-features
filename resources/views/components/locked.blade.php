@props(['feature', 'scope' => null])
@php
    $features = app(\Packstub\Features\Features::class);
    $definition = $features->feature($feature);
    $url = $features->upgradeUrl($scope);
@endphp
@if (! $features->active($feature, $scope))
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-white/10']) }} data-locked-feature="{{ $feature }}">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('packstub-features::features.locked.title', ['feature' => $definition->label]) }}</div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $slot->isEmpty() ? __('packstub-features::features.locked.body') : $slot }}</p>
        @if ($url)
            <x-filament::button tag="a" :href="$url" class="mt-4" size="sm">{{ __('packstub-features::features.plan.upgrade') }}</x-filament::button>
        @endif
    </div>
@endif
