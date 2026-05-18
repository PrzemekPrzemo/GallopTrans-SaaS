<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Raport: {{ $start->translatedFormat('LLLL Y') }}</h2>
            <div class="flex gap-2 text-sm">
                <a href="{{ route('reports.export', [$start->year, $start->month, 'csv']) }}" class="px-3 py-1.5 bg-white border rounded hover:bg-gray-50">📥 CSV</a>
                <a href="{{ route('reports.export', [$start->year, $start->month, 'pdf']) }}" class="px-3 py-1.5 bg-white border rounded hover:bg-gray-50">📄 PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Ofert</div>
                    <div class="text-2xl font-semibold">{{ $summary['quotes_count'] }}</div>
                </div>
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Wartość ofert (brutto)</div>
                    <div class="text-2xl font-semibold">{{ number_format($summary['quotes_gross'], 2, ',', ' ') }}</div>
                </div>
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Wpłaty (brutto)</div>
                    <div class="text-2xl font-semibold text-emerald-700">{{ number_format($summary['paid_gross'], 2, ',', ' ') }}</div>
                </div>
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="text-sm text-gray-500">Należności</div>
                    <div class="text-2xl font-semibold text-amber-700">{{ number_format($summary['outstanding'], 2, ',', ' ') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                <div class="bg-white rounded shadow-sm p-5">
                    <div class="font-medium mb-3">Oferty</div>
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 border-b text-left">
                            <tr><th class="py-1.5">Numer</th><th>Klient</th><th class="text-right">Brutto</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($quotes as $q)
                                <tr>
                                    <td class="py-1.5 font-mono"><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td>{{ $q->client_name }}</td>
                                    <td class="text-right">{{ number_format((float) $q->total_gross, 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded shadow-sm p-5">
                    <div class="font-medium mb-3">Wpłaty</div>
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 border-b text-left">
                            <tr><th class="py-1.5">Data</th><th>Oferta</th><th class="text-right">Brutto</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($payments as $p)
                                <tr>
                                    <td class="py-1.5">{{ $p->paid_at->format('Y-m-d') }}</td>
                                    <td><a class="text-indigo-600" href="{{ route('quotes.show', $p->quote) }}">{{ $p->quote?->number }}</a></td>
                                    <td class="text-right">{{ number_format((float) $p->amount_gross, 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
