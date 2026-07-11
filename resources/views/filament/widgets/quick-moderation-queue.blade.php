<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Red za moderaciju</x-slot>

        <div class="grid grid-cols-3 divide-x divide-neutral-200 dark:divide-neutral-700 border border-neutral-200 dark:border-neutral-700 rounded-sm overflow-hidden">

            <a href="{{ route('filament.admin.resources.products.index', ['tab' => 'pending_review']) }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Oglasi · pending</p>
                <p @class([
                    'text-4xl font-bold',
                    'text-amber-600 dark:text-amber-400' => $pendingProducts > 0,
                    'text-neutral-300 dark:text-neutral-600' => $pendingProducts === 0,
                ])>{{ $pendingProducts }}</p>
                <p class="text-xs text-neutral-500">{{ $pendingProducts > 0 ? 'Čekaju ručno odobrenje →' : 'Sve pregledano ✓' }}</p>
            </a>

            <a href="{{ route('filament.admin.resources.product-reports.index') }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Prijave oglasa · open</p>
                <p @class([
                    'text-4xl font-bold',
                    'text-red-600 dark:text-red-400' => $openProductReports > 0,
                    'text-neutral-300 dark:text-neutral-600' => $openProductReports === 0,
                ])>{{ $openProductReports }}</p>
                <p class="text-xs text-neutral-500">{{ $openProductReports > 0 ? 'Neriješene prijave →' : 'Nema otvorenih prijava ✓' }}</p>
            </a>

            <a href="{{ route('filament.admin.resources.user-reports.index') }}"
               class="flex flex-col gap-2 p-5 bg-white dark:bg-neutral-900 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition">
                <p class="text-xs font-medium text-neutral-400 uppercase tracking-widest">Prijave korisnika · open</p>
                <p @class([
                    'text-4xl font-bold',
                    'text-red-600 dark:text-red-400' => $openUserReports > 0,
                    'text-neutral-300 dark:text-neutral-600' => $openUserReports === 0,
                ])>{{ $openUserReports }}</p>
                <p class="text-xs text-neutral-500">{{ $openUserReports > 0 ? 'Neriješene prijave →' : 'Nema otvorenih prijava ✓' }}</p>
            </a>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
