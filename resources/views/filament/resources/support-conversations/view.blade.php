<x-filament-panels::page>
    {{-- Sticky user header --}}
    <div class="sticky top-0 z-10 -mx-4 -mt-4 px-4 pt-4 pb-3 mb-6 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800 shadow-sm">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                @if ($record->participantOne?->avatar)
                    <div style="width:40px;height:40px;min-width:40px;border-radius:50%;overflow:hidden;box-shadow:0 0 0 2px #e5e7eb;">
                        <img src="{{ $record->participantOne->avatar }}" style="width:40px;height:40px;object-fit:cover;" />
                    </div>
                @else
                    <div style="width:40px;height:40px;min-width:40px;border-radius:50%;background:#171717;color:#fff;display:grid;place-items:center;font-weight:800;font-size:14px;">
                        {{ strtoupper(substr($record->participantOne?->name ?? '?', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <p style="font-weight:800;line-height:1.2;">{{ $record->participantOne?->name }}</p>
                    <p style="font-family:monospace;font-size:11px;color:#9ca3af;">{{ '@' . ($record->participantOne?->username ?? '') }} · {{ $record->participantOne?->email }}</p>
                </div>
            </div>

            <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
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
    <div style="display:flex;flex-direction:column;gap:20px;max-width:720px;margin:0 auto;padding-bottom:32px;">
        @forelse ($record->messages as $msg)
            @php
                $isAdmin = $msg->sender?->isAdmin() || $msg->sender?->is_system;
                $initials = strtoupper(substr($msg->sender?->name ?? '?', 0, 1));
                $time = $msg->created_at->format('d.m.Y. H:i');
            @endphp

            <div style="display:flex;flex-direction:{{ $isAdmin ? 'row-reverse' : 'row' }};align-items:flex-end;gap:10px;">

                {{-- Avatar --}}
                @if ($isAdmin)
                    <div style="width:32px;height:32px;min-width:32px;border-radius:50%;background:#FB5C90;color:#fff;display:grid;place-items:center;font-size:9px;font-weight:800;letter-spacing:0.1em;flex-shrink:0;">
                        TV
                    </div>
                @else
                    <div style="width:32px;height:32px;min-width:32px;border-radius:50%;background:#e5e7eb;color:#374151;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;">
                        {{ $initials }}
                    </div>
                @endif

                {{-- Bubble + meta --}}
                <div style="display:flex;flex-direction:column;gap:4px;max-width:72%;align-items:{{ $isAdmin ? 'flex-end' : 'flex-start' }};">
                    <p style="font-family:monospace;font-size:10px;text-transform:uppercase;letter-spacing:0.12em;color:#9ca3af;padding:0 4px;">
                        {{ $isAdmin ? 'Tavan Podrška' : '@' . $msg->sender?->username }}
                        <span style="color:#d1d5db;">·</span>
                        {{ $time }}
                    </p>

                    @if ($isAdmin)
                        <div style="padding:12px 16px;font-size:14px;line-height:1.6;white-space:pre-line;word-break:break-word;background:#18181b;color:#fff;border-radius:18px 4px 18px 18px;box-shadow:0 1px 3px rgba(0,0,0,.15);">
                            {{ $msg->body }}
                        </div>
                    @else
                        <div style="padding:12px 16px;font-size:14px;line-height:1.6;white-space:pre-line;word-break:break-word;background:#fff;color:#111827;border:1px solid #e5e7eb;border-radius:4px 18px 18px 18px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                            {{ $msg->body }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="padding:80px 0;text-align:center;">
                <p style="font-size:14px;color:#9ca3af;">Nema poruka u razgovoru.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
