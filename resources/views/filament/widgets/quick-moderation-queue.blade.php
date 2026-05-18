<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Red za moderaciju</x-slot>

        <div class="grid grid-cols-3 divide-x divide-neutral-200 dark:divide-neutral-700 border border-neutral-200 dark:border-neutral-700 rounded-xl overflow-hidden">

            <a href="{{ route('filament.admin.resources.products.index', ['tableFilters[status][value]' => 'pending_review']) }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Oglasi · pending</p>
                <p class="text-4xl font-bold text-neutral-900 dark:text-white">{{ $pendingProducts }}</p>
                <p class="text-xs text-neutral-500">Čekaju ručno odobrenje →</p>
            </a>

            <a href="{{ route('filament.admin.resources.product-reports.index') }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Prijave oglasa · open</p>
                <p class="text-4xl font-bold text-neutral-900 dark:text-white">{{ $openProductReports }}</p>
                <p class="text-xs text-neutral-500">Neriješene prijave →</p>
            </a>

            <a href="{{ route('filament.admin.resources.user-reports.index') }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Prijave korisnika · open</p>
                <p class="text-4xl font-bold text-neutral-900 dark:text-white">{{ $openUserReports }}</p>
                <p class="text-xs text-neutral-500">Neriješene prijave →</p>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
