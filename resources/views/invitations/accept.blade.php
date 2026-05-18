<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Dołącz do {{ $invitation->organization->name }}</h1>
        <p class="text-gray-600 mt-1">Zaproszono Cię jako <strong>{{ $invitation->role }}</strong>. Ustaw hasło, aby utworzyć konto.</p>
    </div>

    @if (session('error')) <div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded mb-4">{{ session('error') }}</div> @endif

    <form method="POST" action="{{ route('invitations.process', $invitation->token) }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label value="Email" />
            <x-text-input type="email" value="{{ $invitation->email }}" disabled class="mt-1 block w-full bg-gray-50" />
        </div>

        <div>
            <x-input-label for="name" value="Twoje imię i nazwisko *" />
            <x-text-input id="name" name="name" type="text" required autofocus class="mt-1 block w-full" value="{{ old('name') }}" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Hasło *" />
            <x-text-input id="password" name="password" type="password" required class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Powtórz hasło *" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full" />
        </div>

        <x-primary-button class="w-full justify-center">Akceptuję i tworzę konto</x-primary-button>
    </form>
</x-guest-layout>
