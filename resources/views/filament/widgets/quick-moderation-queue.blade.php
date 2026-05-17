<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Red za moderaciju</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-neutral-200 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-800">
            <a href="{{ route('filament.admin.resources.products.index', ['tableFilters[status][value]' => 'pending_review']) }}"
               class="bg-white dark:bg-neutral-950 p-5 transition hover:bg-[#FB5C90]/[0.04] dark:hover:bg-[#FB5C90]/[0.12]">
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-neutral-500">Oglasi · pending review</p>
                <p class="font-display text-4xl font-extrabold mt-2 tracking-tight">{{ $pendingProducts }}</p>
                <p class="text-xs text-neutral-500 mt-3">Čekaju ručno odobrenje →</p>
            </a>

            <a href="{{ route('filament.admin.resources.product-reports.index') }}"
               class="bg-white dark:bg-neutral-950 p-5 transition hover:bg-[#FB5C90]/[0.04] dark:hover:bg-[#FB5C90]/[0.12]">
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-neutral-500">Prijave oglasa · open</p>
                <p class="font-display text-4xl font-extrabold mt-2 tracking-tight">{{ $openProductReports }}</p>
                <p class="text-xs text-neutral-500 mt-3">Neriješene prijave →</p>
            </a>

            <a href="{{ route('filament.admin.resources.user-reports.index') }}"
               class="bg-white dark:bg-neutral-950 p-5 transition hover:bg-[#FB5C90]/[0.04] dark:hover:bg-[#FB5C90]/[0.12]">
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-neutral-500">Prijave korisnika · open</p>
                <p class="font-display text-4xl font-extrabold mt-2 tracking-tight">{{ $openUserReports }}</p>
                <p class="text-xs text-neutral-500 mt-3">Neriješene prijave →</p>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
