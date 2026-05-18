<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Raporty miesięczne</h2></x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-5 py-2">Miesiąc</th>
                            <th class="px-5 py-2 text-right">Liczba ofert</th>
                            <th class="px-5 py-2 text-right">Wartość ofert (brutto)</th>
                            <th class="px-5 py-2 text-right">Wpłaty (brutto)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($months as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-2">
                                    <a class="text-indigo-600" href="{{ route('reports.month', [$m['year'], $m['month']]) }}">{{ $m['label'] }}</a>
                                </td>
                                <td class="px-5 py-2 text-right">{{ $m['quotes_count'] }}</td>
                                <td class="px-5 py-2 text-right">{{ number_format($m['quotes_gross'], 2, ',', ' ') }}</td>
                                <td class="px-5 py-2 text-right">{{ number_format($m['paid_gross'], 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
