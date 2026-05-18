<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $vehicle->exists ? 'Edycja pojazdu' : 'Nowy pojazd' }}</h2></x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST"
                  action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @if ($vehicle->exists) @method('PUT') @endif

                <div class="grid grid-cols-2 gap-4">
                    <label class="text-sm">Nazwa *
                        <input name="name" type="text" required value="{{ old('name', $vehicle->name) }}" class="mt-1 w-full rounded border-gray-300"></label>
                    <label class="text-sm">Numer rejestracyjny
                        <input name="plate" type="text" value="{{ old('plate', $vehicle->plate) }}" class="mt-1 w-full rounded border-gray-300"></label>

                    <label class="text-sm">Rodzaj paliwa *
                        <select name="fuel_type" class="mt-1 w-full rounded border-gray-300">
                            @foreach (['diesel', 'petrol', 'lpg', 'electric'] as $ft)
                                <option value="{{ $ft }}" {{ ($vehicle->fuel_type ?? 'diesel') === $ft ? 'selected' : '' }}>{{ $ft }}</option>
                            @endforeach
                        </select></label>
                    <label class="text-sm">Spalanie (l/100km) *
                        <input name="fuel_consumption" type="number" step="0.01" required value="{{ old('fuel_consumption', $vehicle->fuel_consumption) }}" class="mt-1 w-full rounded border-gray-300"></label>

                    <label class="text-sm">Liczba koni *
                        <input name="horse_capacity" type="number" min="1" required value="{{ old('horse_capacity', $vehicle->horse_capacity ?? 2) }}" class="mt-1 w-full rounded border-gray-300"></label>
                    <label class="text-sm">Masa całkowita (kg)
                        <input name="max_weight_kg" type="number" value="{{ old('max_weight_kg', $vehicle->max_weight_kg) }}" class="mt-1 w-full rounded border-gray-300"></label>

                    <label class="text-sm">Wysokość (m)
                        <input name="height_m" type="number" step="0.01" value="{{ old('height_m', $vehicle->height_m) }}" class="mt-1 w-full rounded border-gray-300"></label>
                    <label class="text-sm">Długość (m)
                        <input name="length_m" type="number" step="0.01" value="{{ old('length_m', $vehicle->length_m) }}" class="mt-1 w-full rounded border-gray-300"></label>
                    <label class="text-sm">Szerokość (m)
                        <input name="width_m" type="number" step="0.01" value="{{ old('width_m', $vehicle->width_m) }}" class="mt-1 w-full rounded border-gray-300"></label>
                    <label class="text-sm">Liczba osi
                        <input name="axles" type="number" min="2" value="{{ old('axles', $vehicle->axles) }}" class="mt-1 w-full rounded border-gray-300"></label>
                </div>

                <div class="flex gap-6">
                    <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ ($vehicle->is_active ?? true) ? 'checked' : '' }}> Aktywny</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" {{ $vehicle->is_default ? 'checked' : '' }}> Domyślny</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="is_trailer" value="0"><input type="checkbox" name="is_trailer" value="1" {{ $vehicle->is_trailer ? 'checked' : '' }}> Przyczepa</label>
                </div>

                <label class="text-sm block">Notatki
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $vehicle->notes) }}</textarea>
                </label>

                <div class="flex justify-between items-center">
                    @if ($vehicle->exists)
                        <button form="del" class="text-red-600 text-sm">Usuń</button>
                    @endif
                    <div class="flex gap-2 ml-auto">
                        <a href="{{ route('vehicles.index') }}" class="px-4 py-2 bg-gray-100 rounded">Anuluj</a>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Zapisz</button>
                    </div>
                </div>
            </form>

            @if ($vehicle->exists)
                <form id="del" method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Usunąć pojazd?')">@csrf @method('DELETE')</form>
            @endif
        </div>
    </div>
</x-app-layout>
