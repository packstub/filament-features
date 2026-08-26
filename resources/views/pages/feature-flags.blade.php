<x-filament-panels::page>
    @php $plans = \Packstub\Features\Facades\Features::plans(); @endphp
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">{{ __('packstub-features::features.fields.feature') }}</th>
                    <th class="px-4 py-3">{{ __('packstub-features::features.fields.default') }}</th>
                    @foreach ($plans as $plan)
                        <th class="px-4 py-3">{{ $plan->label }}</th>
                    @endforeach
                    <th class="px-4 py-3">{{ __('packstub-features::features.flags.global') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($this->getRows() as $row)
                    <tr data-feature="{{ $row['key'] }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $row['feature']->label }}</div>
                            <div class="font-mono text-xs text-gray-500">{{ $row['key'] }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['default'] }}</td>
                        @foreach ($row['plans'] as $value)
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $value }}</td>
                        @endforeach
                        <td class="px-4 py-3">
                            @if ($row['global'] !== null)
                                <x-filament::badge color="warning">{{ $row['global'] }}</x-filament::badge>
                                @if ($row['global_expires'])
                                    <div class="text-xs text-gray-500">{{ __('packstub-features::features.flags.until', ['time' => $row['global_expires']->diffForHumans()]) }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
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
    <x-filament-actions::modals />
</x-filament-panels::page>
