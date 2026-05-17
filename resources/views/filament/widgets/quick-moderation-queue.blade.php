<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Red za moderaciju</x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Oglasi na čekanju --}}
            <a href="{{ route('filament.admin.resources.products.index', ['tableFilters[status][value]' => 'pending_review']) }}"
               class="group flex flex-col gap-3 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 transition hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 dark:bg-amber-900/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                        Oglasi
                    </span>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-neutral-400">pending</span>
                </div>

                <p class="text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-white leading-none">
                    {{ $pendingProducts }}
                </p>

                <p class="flex items-center gap-1 text-xs text-neutral-500 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">
                    Čekaju ručno odobrenje
                    <svg class="size-3.5 translate-x-0 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </p>
            </a>

            {{-- Prijave oglasa --}}
            <a href="{{ route('filament.admin.resources.product-reports.index') }}"
               class="group flex flex-col gap-3 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 transition hover:border-rose-400 dark:hover:border-rose-500 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 dark:bg-rose-900/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                        Prijave oglasa
                    </span>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-neutral-400">open</span>
                </div>

                <p class="text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-white leading-none">
                    {{ $openProductReports }}
                </p>

                <p class="flex items-center gap-1 text-xs text-neutral-500 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition">
                    Neriješene prijave
                    <svg class="size-3.5 translate-x-0 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </p>
            </a>

            {{-- Prijave korisnika --}}
            <a href="{{ route('filament.admin.resources.user-reports.index') }}"
               class="group flex flex-col gap-3 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 transition hover:border-rose-400 dark:hover:border-rose-500 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 dark:bg-rose-900/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                        Prijave korisnika
                    </span>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-neutral-400">open</span>
                </div>

                <p class="text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-white leading-none">
                    {{ $openUserReports }}
                </p>

                <p class="flex items-center gap-1 text-xs text-neutral-500 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition">
                    Neriješene prijave
                    <svg class="size-3.5 translate-x-0 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </p>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
