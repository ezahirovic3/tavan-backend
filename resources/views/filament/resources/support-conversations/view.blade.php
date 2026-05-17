<x-filament-panels::page>
    {{-- Header status row --}}
    <div class="mb-6 flex flex-wrap items-center gap-3 border-b border-neutral-200 dark:border-neutral-800 pb-4">
        <div class="flex items-center gap-3">
            @if ($record->participantOne?->avatar)
                <img src="{{ $record->participantOne->avatar }}"
                     class="h-10 w-10 object-cover ring-1 ring-neutral-200" />
            @else
                <div class="h-10 w-10 bg-[#0A0A0A] dark:bg-white dark:text-[#0A0A0A] text-white grid place-items-center font-display font-extrabold text-sm">
                    {{ strtoupper(substr($record->participantOne?->name ?? '?', 0, 1)) }}
                </div>
            @endif
            <div>
                <p class="font-display text-lg font-extrabold tracking-tight">{{ $record->participantOne?->name }}</p>
                <p class="font-mono text-xs text-neutral-500">@ {{ $record->participantOne?->username }} · {{ $record->participantOne?->email }}</p>
            </div>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <x-filament::badge :color="$record->status === 'resolved' ? 'success' : 'warning'">
                {{ $record->status === 'resolved' ? 'Riješen' : 'Otvoren' }}
            </x-filament::badge>
            <x-filament::badge :color="$record->allow_replies ? 'success' : 'gray'">
                {{ $record->allow_replies ? 'Odgovori uključeni' : 'Odgovori zaključani' }}
            </x-filament::badge>
        </div>
    </div>

    {{-- Message thread --}}
    <div class="space-y-4 max-w-3xl">
        @foreach ($record->messages as $msg)
            @php $isAdmin = $msg->sender?->isAdmin(); @endphp
            <div @class([
                'flex gap-3',
                'flex-row-reverse' => $isAdmin,
            ])>
                <div class="shrink-0">
                    @if ($isAdmin)
                        <div class="h-8 w-8 bg-[#FB5C90] text-white grid place-items-center font-display font-extrabold text-[10px] tracking-widest">TV</div>
                    @else
                        <div class="h-8 w-8 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-200 grid place-items-center font-display font-bold text-xs">
                            {{ strtoupper(substr($msg->sender?->name ?? '?', 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div @class([
                    'flex-1 min-w-0',
                    'text-right' => $isAdmin,
                ])>
                    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-neutral-500 mb-1">
                        {{ $isAdmin ? 'Tavan Podrška' : '@' . $msg->sender?->username }}
                        · {{ $msg->created_at->format('d.m.Y. H:i') }}
                    </p>
                    <div @class([
                        'inline-block px-4 py-3 max-w-prose text-sm whitespace-pre-line',
                        'bg-[#0A0A0A] text-white' => $isAdmin,
                        'bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 text-neutral-900 dark:text-neutral-100' => ! $isAdmin,
                    ])>
                        {{ $msg->body }}
                    </div>
                </div>
            </div>
        @endforeach

        @if ($record->messages->isEmpty())
            <p class="text-center text-sm text-neutral-500 py-12">Nema poruka u razgovoru.</p>
        @endif
    </div>
</x-filament-panels::page>
