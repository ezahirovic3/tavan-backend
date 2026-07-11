{{-- resources/views/filament/widgets/recent-activity-feed.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Aktivnost</x-slot>
        <x-slot name="description">Posljednje radnje administratora</x-slot>

        <ol class="divide-y divide-neutral-200 dark:divide-neutral-800 -mx-6 max-h-96 overflow-y-auto">
            @forelse ($activities as $a)
                <li class="px-6 py-3 flex items-start gap-3 group">
                    <span @class([
                        'mt-1.5 inline-block h-1.5 w-1.5 shrink-0',
                        'bg-[#1D781C]'  => $a['color'] === 'success',
                        'bg-amber-500'  => $a['color'] === 'warning',
                        'bg-red-600'    => $a['color'] === 'danger',
                        'bg-neutral-400' => $a['color'] === 'gray',
                    ])></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-mono uppercase tracking-wide text-neutral-500">
                            {{ $a['when']->diffForHumans() }}
                        </p>
                        <p class="text-sm text-neutral-900 dark:text-neutral-100 mt-0.5 truncate">
                            <span class="font-semibold">{{ $a['who'] }}</span>
                            <span class="text-neutral-500">{{ strtolower($a['event']) }} {{ $a['type'] }}</span>
                            <span class="font-medium">{{ $a['subject'] }}</span>
                        </p>
                    </div>
                </li>
            @empty
                <li class="px-6 py-8 text-center text-sm text-neutral-500">Nema aktivnosti</li>
            @endforelse
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
