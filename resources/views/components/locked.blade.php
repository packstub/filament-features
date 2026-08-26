@props(['feature', 'scope' => null])
@php
    $features = app(\Packstub\Features\Features::class);
    $definition = $features->feature($feature);
    $url = $features->upgradeUrl($scope);
@endphp
@if (! $features->active($feature, $scope))
    <div {{ $attributes }} data-locked-feature="{{ $feature }}" style="border:1px dashed rgb(0 0 0 / .18);border-radius:.75rem;padding:1.5rem;text-align:center">
        <div style="font-size:.875rem;font-weight:600">{{ __('packstub-features::features.locked.title', ['feature' => $definition->label]) }}</div>
        <p style="margin-top:.25rem;font-size:.875rem;opacity:.65">{{ $slot->isEmpty() ? __('packstub-features::features.locked.body') : $slot }}</p>
        @if ($url)
            <div style="margin-top:1rem"><x-filament::button tag="a" :href="$url" size="sm">{{ __('packstub-features::features.plan.upgrade') }}</x-filament::button></div>
        @endif
    </div>
@endif
