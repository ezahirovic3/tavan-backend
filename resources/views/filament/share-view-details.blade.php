<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead>
            <tr class="border-b border-gray-700 text-xs uppercase text-gray-400">
                <th class="py-2 pr-4">Platforma</th>
                <th class="py-2 pr-4">Ishod</th>
                <th class="py-2 pr-4">Izvor</th>
                <th class="py-2">Vrijeme</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr class="border-b border-gray-800">
                    <td class="py-2 pr-4">
                        @php
                            $platformLabel = match($event->platform) {
                                'ios' => 'iOS',
                                'android' => 'Android',
                                default => 'Desktop',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                            {{ $event->platform === 'ios' ? 'bg-blue-400/10 text-blue-400 ring-blue-400/20' : ($event->platform === 'android' ? 'bg-green-400/10 text-green-400 ring-green-400/20' : 'bg-gray-400/10 text-gray-400 ring-gray-400/20') }}">
                            {{ $platformLabel }}
                        </span>
                    </td>
                    <td class="py-2 pr-4">
                        @php
                            $outcomeLabel = match($event->outcome) {
                                'app_opened' => 'Otvorio app',
                                'store_redirect' => 'Store redirect',
                                default => 'Nepoznato',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                            {{ $event->outcome === 'app_opened' ? 'bg-green-400/10 text-green-400 ring-green-400/20' : ($event->outcome === 'store_redirect' ? 'bg-yellow-400/10 text-yellow-400 ring-yellow-400/20' : 'bg-gray-400/10 text-gray-400 ring-gray-400/20') }}">
                            {{ $outcomeLabel }}
                        </span>
                    </td>
                    <td class="py-2 pr-4 text-gray-400">
                        {{ match($event->referrer_platform) {
                            'instagram' => 'Instagram',
                            'whatsapp' => 'WhatsApp',
                            'facebook' => 'Facebook',
                            'twitter' => 'Twitter/X',
                            'direct' => 'Direktno',
                            default => 'Ostalo',
                        } }}
                    </td>
                    <td class="py-2 text-gray-400">
                        {{ $event->created_at->format('d.m.Y. H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-4 text-center text-gray-500">Nema podataka.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
