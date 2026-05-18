<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $client->exists ? 'Edycja klienta' : 'Nowy klient' }}</h2></x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST"
                  action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @if ($client->exists) @method('PUT') @endif

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <label>Imię i nazwisko / nazwa *
                        <input name="name" required value="{{ old('name', $client->name) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>Firma
                        <input name="company" value="{{ old('company', $client->company) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>NIP
                        <input name="nip" value="{{ old('nip', $client->nip) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>E-mail
                        <input name="email" type="email" value="{{ old('email', $client->email) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>Telefon
                        <input name="phone" type="tel" value="{{ old('phone', $client->phone) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label class="col-span-2">Adres
                        <input name="address" value="{{ old('address', $client->address) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>Preferowana stawka km
                        <input name="default_rate_per_km" type="number" step="0.01" value="{{ old('default_rate_per_km', $client->default_rate_per_km) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label>Preferowana kwota minimalna
                        <input name="default_min_amount" type="number" step="0.01" value="{{ old('default_min_amount', $client->default_min_amount) }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                    <label class="col-span-2">Notatki
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $client->notes) }}</textarea>
                    </label>
                </div>

                <div class="flex justify-between">
                    @if ($client->exists)
                        <button form="del" class="text-red-600 text-sm">Usuń klienta</button>
                    @endif
                    <div class="flex gap-2 ml-auto">
                        <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-gray-100 rounded">Anuluj</a>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Zapisz</button>
                    </div>
                </div>
            </form>

            @if ($client->exists)
                <form id="del" method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Usunąć klienta? Oferty zostaną zachowane, ale stracą powiązanie.')">@csrf @method('DELETE')</form>
            @endif
        </div>
    </div>
</x-app-layout>
