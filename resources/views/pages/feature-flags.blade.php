<x-filament-panels::page>
    @php $plans = \Packstub\Features\Facades\Features::plans(); @endphp
    <x-filament::section>
        <div style="overflow-x:auto">
            <table style="width:100%;font-size:.875rem;border-collapse:collapse">
                <thead>
                    <tr style="text-align:left;font-size:.7rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;opacity:.6">
                        <th style="padding:.5rem .75rem">{{ __('packstub-features::features.fields.feature') }}</th>
                        <th style="padding:.5rem .75rem">{{ __('packstub-features::features.fields.default') }}</th>
                        @foreach ($plans as $plan)
                            <th style="padding:.5rem .75rem">{{ $plan->label }}</th>
                        @endforeach
                        <th style="padding:.5rem .75rem">{{ __('packstub-features::features.flags.global') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->getRows() as $row)
                        <tr data-feature="{{ $row['key'] }}" style="border-top:1px solid rgb(0 0 0 / .06)">
                            <td style="padding:.75rem">
                                <div style="font-weight:600">{{ $row['feature']->label }}</div>
                                <div style="font-family:ui-monospace,monospace;font-size:.7rem;opacity:.6">{{ $row['key'] }}</div>
                            </td>
                            <td style="padding:.75rem;opacity:.8">{{ $row['default'] }}</td>
                            @foreach ($row['plans'] as $value)
                                <td style="padding:.75rem;opacity:.8">{{ $value }}</td>
                            @endforeach
                            <td style="padding:.75rem">
                                @if ($row['global'] !== null)
                                    <x-filament::badge color="warning">{{ $row['global'] }}</x-filament::badge>
                                    @if ($row['global_expires'])
                                        <div style="font-size:.7rem;opacity:.6">{{ __('packstub-features::features.flags.until', ['time' => $row['global_expires']->diffForHumans()]) }}</div>
                                    @endif
                                @else
                                    <span style="opacity:.4">—</span>
                                @endif
                            </td>
                            <td style="padding:.75rem">
                                <div style="display:flex;justify-content:flex-end;gap:.75rem;white-space:nowrap">
                                    {{ ($this->enableAction)(['feature' => $row['key']]) }}
                                    {{ ($this->disableAction)(['feature' => $row['key']]) }}
                                    @if ($row['global'] !== null)
                                        {{ ($this->clearAction)(['feature' => $row['key']]) }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-panels::page>
