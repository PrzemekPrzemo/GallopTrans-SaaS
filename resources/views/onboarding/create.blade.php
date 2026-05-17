<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Załóż konto firmowe</h1>
        <p class="text-gray-600 mt-1">Jedno konto firmowe = jedna organizacja w GallopTrans. Możesz później zapraszać kolegów i kierowców.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="company_name" value="Nazwa firmy *" />
            <x-text-input id="company_name" name="company_name" type="text" required autofocus
                          class="mt-1 block w-full" value="{{ old('company_name') }}" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="company_address" value="Adres" />
            <x-text-input id="company_address" name="company_address" type="text"
                          class="mt-1 block w-full" value="{{ old('company_address') }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-input-label for="company_nip" value="NIP" />
                <x-text-input id="company_nip" name="company_nip" type="text"
                              class="mt-1 block w-full" value="{{ old('company_nip') }}" />
            </div>
            <div>
                <x-input-label for="company_phone" value="Telefon" />
                <x-text-input id="company_phone" name="company_phone" type="tel"
                              class="mt-1 block w-full" value="{{ old('company_phone') }}" />
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 rounded p-3 text-sm">
            Dostaniesz <strong>14 dni darmowego trialu</strong>. Wybór planu po trialu.
        </div>

        <x-primary-button class="w-full justify-center">Utwórz konto firmowe</x-primary-button>
    </form>
</x-guest-layout>
