<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Zadržavanje</x-slot>
        <x-slot name="description">Povratak korisnika nakon prvog korištenja · kohorta {{ $rangeDays }} dana</x-slot>

        <ul class="divide-y divide-neutral-200 dark:divide-neutral-800 -mx-6">
            @foreach ($rows as $row)
                <li class="px-6 py-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                            Dan {{ $row['day'] }}
                        </span>
                        <span class="text-sm text-neutral-500 whitespace-nowrap">
                            @if ($row['pct'] === null)
                                —
                            @else
                                <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ number_format($row['pct'], 1, ',', '.') }}%</span>
                                · {{ number_format($row['returned'], 0, ',', '.') }} od {{ number_format($row['eligible'], 0, ',', '.') }}
                            @endif
                        </span>
                    </div>
                    <div class="mt-2 h-1 rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                        <div class="h-full bg-[#FB5C90]" style="width: {{ min(100, $row['pct'] ?? 0) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
