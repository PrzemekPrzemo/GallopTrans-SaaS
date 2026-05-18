<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Pojazdy</h2>
            <a href="{{ route('vehicles.create') }}" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm">+ Dodaj pojazd</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-5 py-2">Nazwa</th>
                            <th class="px-5 py-2">Numer rej.</th>
                            <th class="px-5 py-2">Spalanie</th>
                            <th class="px-5 py-2">Koni</th>
                            <th class="px-5 py-2">Masa</th>
                            <th class="px-5 py-2">Status</th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($vehicles as $v)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-2 font-medium">{{ $v->name }} @if ($v->is_default)<span class="text-xs px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded ml-1">domyślny</span>@endif</td>
                                <td class="px-5 py-2">{{ $v->plate ?? '—' }}</td>
                                <td class="px-5 py-2">{{ $v->fuel_consumption }} l/100</td>
                                <td class="px-5 py-2">{{ $v->horse_capacity }}</td>
                                <td class="px-5 py-2">{{ $v->max_weight_kg ?? '—' }} kg</td>
                                <td class="px-5 py-2">{{ $v->is_active ? 'aktywny' : 'nieaktywny' }}</td>
                                <td class="px-5 py-2 text-right">
                                    <a href="{{ route('vehicles.edit', $v) }}" class="text-indigo-600 text-sm">edytuj</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
