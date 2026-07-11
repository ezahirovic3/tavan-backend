<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Korištenje aplikacije</x-slot>
        <x-slot name="description">Aktivnost po danu i satu · {{ $rangeDays }} dana</x-slot>

        @if ($max === 0)
            <p class="py-8 text-center text-sm text-neutral-500">
                Nema podataka — provjeri PostHog konfiguraciju.
            </p>
        @else
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    @foreach ($days as $dow => $label)
                        <div class="flex items-center gap-1 mb-1">
                            <span class="w-8 shrink-0 text-[10px] font-mono uppercase tracking-wider text-neutral-400">{{ $label }}</span>
                            @for ($hour = 0; $hour < 24; $hour++)
                                @php
                                    $value = $grid[$dow][$hour];
                                    $alpha = $value > 0 ? 0.12 + 0.88 * ($value / $max) : 0;
                                @endphp
                                <div
                                    class="h-5 flex-1 rounded-[2px] {{ $value === 0 ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}"
                                    @if ($value > 0) style="background-color: rgba(251, 92, 144, {{ number_format($alpha, 2) }});" @endif
                                    title="{{ $label }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}h — {{ $value }} događaja"
                                ></div>
                            @endfor
                        </div>
                    @endforeach

                    <div class="flex items-center gap-1 mt-1">
                        <span class="w-8 shrink-0"></span>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <span class="flex-1 text-center text-[9px] font-mono text-neutral-400">
                                {{ $hour % 3 === 0 ? str_pad($hour, 2, '0', STR_PAD_LEFT) : '' }}
                            </span>
                        @endfor
                    </div>

                    <p class="mt-3 text-xs text-neutral-500">
                        Ukupno {{ number_format($total, 0, ',', '.') }} događaja u {{ $rangeDays }} dana
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
