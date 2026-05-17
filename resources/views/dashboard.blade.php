<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pulpit</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded">{{ session('warning') }}</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Oferty (łącznie)</div>
                    <div class="text-3xl font-semibold mt-1">{{ $stats['quotes_total'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Oferty (miesiąc)</div>
                    <div class="text-3xl font-semibold mt-1">{{ $stats['quotes_month'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Nowe zapytania</div>
                    <div class="text-3xl font-semibold mt-1">{{ $stats['inquiries_new'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Przychód (miesiąc)</div>
                    <div class="text-3xl font-semibold mt-1">{{ number_format($stats['revenue_month'], 2, ',', ' ') }} zł</div>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('calculator.index') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                    + Nowa wycena (kalkulator)
                </a>
                <a href="{{ route('quotes.index') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-300 text-gray-800 rounded-lg font-medium hover:bg-gray-50">
                    Wszystkie oferty
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Ostatnie oferty</div>
                @if ($recentQuotes->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">Brak ofert. <a class="text-indigo-600" href="{{ route('calculator.index') }}">Utwórz pierwszą wycenę &rarr;</a></div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-5 py-2">Numer</th>
                                <th class="px-5 py-2">Klient</th>
                                <th class="px-5 py-2">Trasa</th>
                                <th class="px-5 py-2 text-right">Brutto</th>
                                <th class="px-5 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($recentQuotes as $q)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-2 font-mono"><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td class="px-5 py-2">{{ $q->client_name }}</td>
                                    <td class="px-5 py-2 text-gray-600">{{ \Illuminate\Support\Str::limit($q->from_address, 25) }} → {{ \Illuminate\Support\Str::limit($q->to_address, 25) }}</td>
                                    <td class="px-5 py-2 text-right">{{ number_format((float) $q->total_gross, 2, ',', ' ') }} {{ $q->currency }}</td>
                                    <td class="px-5 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $q->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
