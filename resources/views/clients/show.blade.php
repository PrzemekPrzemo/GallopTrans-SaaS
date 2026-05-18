<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">{{ $client->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('clients.edit', $client) }}" class="px-3 py-1.5 text-sm bg-white border rounded">Edytuj</a>
                <a href="{{ route('calculator.index', [
                        'client_name' => $client->name, 'client_email' => $client->email,
                        'client_phone' => $client->phone, 'client_company' => $client->company,
                        'client_id' => $client->id,
                   ]) }}" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded">+ Nowa wycena</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div> @endif

            <div class="bg-white rounded-lg shadow-sm p-6 grid grid-cols-2 gap-3 text-sm">
                <div><strong>Firma:</strong> {{ $client->company ?? '—' }}</div>
                <div><strong>NIP:</strong> {{ $client->nip ?? '—' }}</div>
                <div><strong>E-mail:</strong> {{ $client->email ?? '—' }}</div>
                <div><strong>Telefon:</strong> {{ $client->phone ?? '—' }}</div>
                <div class="col-span-2"><strong>Adres:</strong> {{ $client->address ?? '—' }}</div>
                @if ($client->default_rate_per_km)
                    <div><strong>Stawka km (preferencyjna):</strong> {{ number_format($client->default_rate_per_km, 2, ',', ' ') }} zł</div>
                @endif
                @if ($client->default_min_amount)
                    <div><strong>Min. kwota:</strong> {{ number_format($client->default_min_amount, 2, ',', ' ') }} zł</div>
                @endif
                @if ($client->notes)
                    <div class="col-span-2"><strong>Notatki:</strong> {{ $client->notes }}</div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Historia ofert ({{ $client->quotes->count() }})</div>
                @if ($client->quotes->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">Brak.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr><th class="px-4 py-2">Numer</th><th>Data</th><th>Trasa</th><th class="text-right">Brutto</th><th>Status</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($client->quotes as $q)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-mono"><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td class="px-4 py-2 text-gray-500">{{ $q->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ \Illuminate\Support\Str::limit($q->from_address, 22) }} → {{ \Illuminate\Support\Str::limit($q->to_address, 22) }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) $q->total_gross, 2, ',', ' ') }} {{ $q->currency }}</td>
                                    <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $q->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
