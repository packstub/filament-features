<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($this->getGroups() as $group => $rows)
            <x-filament::section :heading="$group">
                <ul class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($rows as $row)
                        <li class="flex items-start justify-between gap-4 py-3" data-feature="{{ $row['key'] }}">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['feature']->label }}</div>
                                @if ($row['feature']->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['feature']->description }}</div>
                                @endif
                                @if ($row['used'] !== null && $row['value'] !== null)
                                    @php $pct = $row['value'] > 0 ? min(100, (int) round($row['used'] / $row['value'] * 100)) : 100; @endphp
                                    <div class="mt-2 h-1.5 w-40 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                        <div class="h-full rounded-full {{ $pct >= 100 ? 'bg-danger-500' : 'bg-primary-500' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('packstub-features::features.plan.usage', ['used' => $row['used'], 'limit' => $row['formatted']]) }}</div>
                                @elseif ($row['used'] !== null)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('packstub-features::features.plan.usage_unlimited', ['used' => $row['used']]) }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <x-filament::badge :color="$row['active'] ? 'success' : 'gray'">{{ $row['formatted'] }}</x-filament::badge>
                                @if ($row['source'] === 'override')
                                    <div class="mt-1 text-[11px] uppercase tracking-wide text-gray-400">{{ __('packstub-features::features.plan.custom') }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
