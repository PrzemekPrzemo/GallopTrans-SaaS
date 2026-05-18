<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Faktury</h2></x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif
            @if (session('error'))   <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">{{ session('error') }}</div> @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if ($invoices->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">
                        Brak faktur. Wystaw fakturę z zaakceptowanej oferty.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-2">Numer</th>
                                <th class="px-4 py-2">Data</th>
                                <th class="px-4 py-2">Klient</th>
                                <th class="px-4 py-2">Oferta</th>
                                <th class="px-4 py-2 text-right">Brutto</th>
                                <th class="px-4 py-2">KSeF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($invoices as $inv)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-mono"><a class="text-indigo-600" href="{{ route('invoices.show', $inv) }}">{{ $inv->number }}</a></td>
                                    <td class="px-4 py-2">{{ $inv->issued_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2">{{ $inv->client_company ?: $inv->client_name }}</td>
                                    <td class="px-4 py-2 font-mono">
                                        @if ($inv->quote) <a class="text-indigo-600" href="{{ route('quotes.show', $inv->quote) }}">{{ $inv->quote->number }}</a> @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $inv->total_gross, 2, ',', ' ') }} {{ $inv->currency }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs px-2 py-0.5 rounded
                                            {{ in_array($inv->ksef_status, ['sent']) ? 'bg-green-100 text-green-800'
                                               : ($inv->ksef_status === 'rejected' ? 'bg-red-100 text-red-800'
                                               : 'bg-gray-100 text-gray-700') }}">{{ $inv->ksef_status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 py-2 border-t">{{ $invoices->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
