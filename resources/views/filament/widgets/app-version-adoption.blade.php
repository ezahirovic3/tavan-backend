<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Verzije aplikacije</x-slot>
        <x-slot name="description">Korisnici po verziji · {{ $rangeDays }} dana</x-slot>

        @if (empty($rows))
            <p class="py-8 text-center text-sm text-neutral-500">Nema podataka</p>
        @else
            <ul class="divide-y divide-neutral-200 dark:divide-neutral-800 -mx-6">
                @foreach ($rows as $row)
                    <li class="px-6 py-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ $row['version'] }}
                                <span class="ml-1 font-normal text-xs text-neutral-500">od {{ $row['since'] }}</span>
                            </span>
                            <span class="text-sm text-neutral-500 whitespace-nowrap">
                                <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ number_format($row['users'], 0, ',', '.') }}</span>
                                korisnika
                            </span>
                        </div>
                        <div class="mt-2 h-1 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                            <div class="h-full bg-[#FB5C90]" style="width: {{ min(100, $row['pct']) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
