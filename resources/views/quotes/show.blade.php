<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Oferta {{ $quote->number }}</h2>
            <a href="{{ route('quotes.index') }}" class="text-sm text-gray-600">← Powrót</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm text-gray-500">Klient</div>
                        <div class="text-lg font-semibold">{{ $quote->client_name }}</div>
                        @if ($quote->client_company) <div class="text-sm text-gray-600">{{ $quote->client_company }} {{ $quote->client_nip ? ' · NIP ' . $quote->client_nip : '' }}</div> @endif
                        @if ($quote->client_email) <div class="text-sm text-gray-600">{{ $quote->client_email }}</div> @endif
                        @if ($quote->client_phone) <div class="text-sm text-gray-600">{{ $quote->client_phone }}</div> @endif
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="text-lg font-semibold">{{ $quote->status }}</div>
                        @if ($quote->valid_until)
                            <div class="text-sm text-gray-500 mt-2">Ważna do</div>
                            <div>{{ $quote->valid_until->format('Y-m-d') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-500">Skąd</div>
                        <div>{{ $quote->from_address }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Dokąd</div>
                        <div>{{ $quote->to_address }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Dystans</div>
                        <div>{{ number_format((float) $quote->distance_km, 2, ',', ' ') }} km · {{ intdiv($quote->duration_min, 60) }} h {{ $quote->duration_min % 60 }} min</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Tryb</div>
                        <div>{{ $quote->trip_mode }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Konie</div>
                        <div>{{ $quote->horses_count }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Data transportu</div>
                        <div>{{ $quote->transport_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="font-medium mb-3">Pozycje</div>
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b text-left">
                        <tr><th class="py-2">Opis</th><th class="py-2 text-right">Ilość</th><th class="py-2 text-right">Cena netto</th><th class="py-2 text-right">Suma netto</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($quote->items as $it)
                            <tr>
                                <td class="py-2">{{ $it->description }}</td>
                                <td class="py-2 text-right">{{ rtrim(rtrim(number_format($it->qty, 2, ',', ' '), '0'), ',') }} {{ $it->unit }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->unit_price_net, 2, ',', ' ') }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->total_net, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-medium">
                        <tr><td colspan="3" class="py-2 text-right">Netto</td><td class="py-2 text-right">{{ number_format((float) $quote->subtotal_net, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                        <tr><td colspan="3" class="py-2 text-right">VAT ({{ number_format((float) $quote->vat_percent, 0) }}%)</td><td class="py-2 text-right">{{ number_format((float) $quote->vat_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                        <tr><td colspan="3" class="py-2 text-right text-lg">Brutto</td><td class="py-2 text-right text-lg">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 text-sm text-gray-600">
                Link publiczny dla klienta: <a class="text-indigo-600 break-all" href="{{ route('quotes.public', $quote->public_token) }}">{{ route('quotes.public', $quote->public_token) }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
