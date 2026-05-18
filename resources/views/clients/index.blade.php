<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Klienci</h2>
            <a href="{{ route('clients.create') }}" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm">+ Dodaj klienta</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                @if ($clients->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">
                        Brak klientów. Pojawią się tu automatycznie po zapisaniu pierwszej wyceny.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th class="px-4 py-2">Klient</th>
                                <th class="px-4 py-2">Firma / NIP</th>
                                <th class="px-4 py-2">Kontakt</th>
                                <th class="px-4 py-2 text-right">Wycen</th>
                                <th class="px-4 py-2 text-right">Przychód (zaakcept.)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($clients as $c)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium"><a href="{{ route('clients.show', $c) }}" class="text-indigo-600">{{ $c->name }}</a></td>
                                    <td class="px-4 py-2 text-gray-600">{{ $c->company }} {{ $c->nip ? '· NIP ' . $c->nip : '' }}</td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">{{ $c->email }}<br>{{ $c->phone }}</td>
                                    <td class="px-4 py-2 text-right">{{ $c->quotes_count }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((float) ($c->accepted_gross ?? 0), 2, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 py-2 border-t">{{ $clients->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
