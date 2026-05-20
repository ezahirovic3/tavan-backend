<x-filament-panels::page>
    {{-- Sticky user header --}}
    <div class="sticky top-0 z-10 -mx-4 -mt-4 px-4 pt-4 pb-3 mb-6 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-3">
                @if ($record->participantOne?->avatar)
                    <div class="shrink-0 rounded-full overflow-hidden ring-2 ring-neutral-200 dark:ring-neutral-700" style="width:40px;height:40px;min-width:40px;">
                        <img src="{{ $record->participantOne->avatar }}"
                             style="width:40px;height:40px;object-fit:cover;" />
                    </div>
                @else
                    <div class="shrink-0 rounded-full bg-neutral-900 dark:bg-white dark:text-neutral-900 text-white grid place-items-center font-display font-extrabold text-sm" style="width:40px;height:40px;min-width:40px;">
                        {{ strtoupper(substr($record->participantOne?->name ?? '?', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p class="font-display font-extrabold tracking-tight leading-tight">{{ $record->participantOne?->name }}</p>
                    <p class="font-mono text-xs text-neutral-400">{{ '@' . ($record->participantOne?->username ?? '') }} · {{ $record->participantOne?->email }}</p>
                </div>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <x-filament::badge :color="$record->status === 'resolved' ? 'success' : 'warning'">
                    {{ $record->status === 'resolved' ? 'Riješen' : 'Otvoren' }}
                </x-filament::badge>
                <x-filament::badge :color="$record->allow_replies ? 'success' : 'gray'">
                    {{ $record->allow_replies ? 'Odgovori uključeni' : 'Zaključano' }}
                </x-filament::badge>
            </div>
        </div>
    </div>

    {{-- Message thread --}}
    <div class="flex flex-col gap-6 max-w-2xl mx-auto pb-8">
        @forelse ($record->messages as $msg)
            @php $isAdmin = $msg->sender?->isAdmin(); @endphp

            <div @class(['flex gap-3', 'flex-row-reverse' => $isAdmin])>
                {{-- Avatar --}}
                <div class="shrink-0 mt-1">
                    @if ($isAdmin)
                        <div class="h-8 w-8 rounded-full bg-[#FB5C90] text-white grid place-items-center text-[10px] font-extrabold tracking-widest">TV</div>
                    @else
                        <div class="h-8 w-8 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 grid place-items-center font-bold text-xs">
                            {{ strtoupper(substr($msg->sender?->name ?? '?', 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Bubble + meta --}}
                <div @class(['flex flex-col gap-1 max-w-[75%]', 'items-end' => $isAdmin, 'items-start' => ! $isAdmin])>
                    <p class="font-mono text-[10px] uppercase tracking-[0.15em] text-neutral-400 px-1">
                        {{ $isAdmin ? 'Tavan Podrška' : '@' . $msg->sender?->username }}
                        <span class="text-neutral-300 dark:text-neutral-600">·</span>
                        {{ $msg->created_at->format('d.m.Y. H:i') }}
                    </p>

                    <div @class([
                        'px-4 py-3 text-sm leading-relaxed whitespace-pre-line break-words shadow-sm',
                        'bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 rounded-2xl rounded-tr-sm' => $isAdmin,
                        'bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-900 dark:text-neutral-100 rounded-2xl rounded-tl-sm' => ! $isAdmin,
                    ])>{{ $msg->body }}</div>
                </div>
            </div>
        @empty
            <div class="py-20 text-center">
                <p class="text-sm text-neutral-400">Nema poruka u razgovoru.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
