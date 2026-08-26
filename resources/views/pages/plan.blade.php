<x-filament-panels::page>
    <div style="display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fit,minmax(22rem,1fr))">
        @foreach ($this->getGroups() as $group => $rows)
            <x-filament::section :heading="$group">
                <div style="display:flex;flex-direction:column">
                    @foreach ($rows as $row)
                        <div data-feature="{{ $row['key'] }}" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:.75rem 0;{{ $loop->first ? '' : 'border-top:1px solid rgb(0 0 0 / .06)' }}">
                            <div style="min-width:0">
                                <div style="font-size:.875rem;font-weight:600">{{ $row['feature']->label }}</div>
                                @if ($row['feature']->description)
                                    <div style="font-size:.75rem;opacity:.65">{{ $row['feature']->description }}</div>
                                @endif
                                @if ($row['used'] !== null && $row['value'] !== null)
                                    @php $pct = $row['value'] > 0 ? min(100, (int) round($row['used'] / $row['value'] * 100)) : 100; @endphp
                                    <div style="margin-top:.5rem;height:.375rem;width:11rem;overflow:hidden;border-radius:999px;background:rgb(0 0 0 / .08)">
                                        <div style="height:100%;border-radius:999px;width:{{ $pct }}%;background:{{ $pct >= 100 ? 'rgb(220 38 38)' : 'var(--color-primary-600, #4f46e5)' }}"></div>
                                    </div>
                                    <div style="margin-top:.25rem;font-size:.75rem;opacity:.65">{{ __('packstub-features::features.plan.usage', ['used' => $row['used'], 'limit' => $row['formatted']]) }}</div>
                                @elseif ($row['used'] !== null)
                                    <div style="margin-top:.25rem;font-size:.75rem;opacity:.65">{{ __('packstub-features::features.plan.usage_unlimited', ['used' => $row['used']]) }}</div>
                                @endif
                            </div>
                            <div style="flex:none;text-align:right">
                                <x-filament::badge :color="$row['active'] ? 'success' : 'gray'">{{ $row['formatted'] }}</x-filament::badge>
                                @if ($row['source'] === 'override')
                                    <div style="margin-top:.25rem;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;opacity:.5">{{ __('packstub-features::features.plan.custom') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
