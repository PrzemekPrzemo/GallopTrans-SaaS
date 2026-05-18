<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Oferty</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if ($quotes->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">
                        Brak ofert. <a class="text-indigo-600" href="{{ route('calculator.index') }}">Utwórz pierwszą wycenę &rarr;</a>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-5 py-2">Numer</th>
                                <th class="px-5 py-2">Klient</th>
                                <th class="px-5 py-2">Trasa</th>
                                <th class="px-5 py-2">Data</th>
                                <th class="px-5 py-2 text-right">Brutto</th>
                                <th class="px-5 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($quotes as $q)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-2 font-mono"><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td class="px-5 py-2">{{ $q->client_name }}</td>
                                    <td class="px-5 py-2 text-gray-600">{{ \Illuminate\Support\Str::limit($q->from_address, 22) }} → {{ \Illuminate\Support\Str::limit($q->to_address, 22) }}</td>
                                    <td class="px-5 py-2">{{ $q->transport_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-5 py-2 text-right">{{ number_format((float) $q->total_gross, 2, ',', ' ') }} {{ $q->currency }}</td>
                                    <td class="px-5 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $q->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-5 py-3 border-t">{{ $quotes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
