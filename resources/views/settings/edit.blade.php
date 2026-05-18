<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Ustawienia organizacji</h2></x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif

            <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                @csrf

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="font-medium mb-3 text-gray-700 uppercase text-sm">Dane firmy</div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <label>Nazwa firmy
                            <input name="organization[name]" value="{{ $organization->name }}" class="mt-1 w-full rounded border-gray-300"></label>
                        <label>NIP
                            <input name="organization[company_nip]" value="{{ $organization->company_nip }}" class="mt-1 w-full rounded border-gray-300"></label>
                        <label class="col-span-2">Adres
                            <input name="organization[company_address]" value="{{ $organization->company_address }}" class="mt-1 w-full rounded border-gray-300"></label>
                        <label>Telefon
                            <input name="organization[company_phone]" value="{{ $organization->company_phone }}" class="mt-1 w-full rounded border-gray-300"></label>
                        <label>E-mail
                            <input name="organization[company_email]" value="{{ $organization->company_email }}" class="mt-1 w-full rounded border-gray-300"></label>
                        <label class="col-span-2">Numer rachunku bankowego
                            <input name="organization[company_bank]" value="{{ $organization->company_bank }}" class="mt-1 w-full rounded border-gray-300"></label>
                    </div>
                </div>

                @foreach ($byGroup as $group => $items)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="font-medium mb-3 text-gray-700 uppercase text-sm">{{ $group }}</div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @foreach ($items as $s)
                                <label class="block">
                                    <span class="text-gray-700">{{ $s->label ?? $s->key }}</span>
                                    @if ($s->type === 'bool')
                                        <select name="settings[{{ $s->key }}]" class="mt-1 w-full rounded border-gray-300">
                                            <option value="1" {{ $s->value == '1' ? 'selected' : '' }}>tak</option>
                                            <option value="0" {{ $s->value == '0' ? 'selected' : '' }}>nie</option>
                                        </select>
                                    @else
                                        <input name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="mt-1 w-full rounded border-gray-300">
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="font-medium mb-3 text-gray-700 uppercase text-sm">Routing (Openrouteservice)</div>
                    <label class="block text-sm">Klucz ORS_API_KEY (per-organizacja, ma pierwszeństwo nad ENV)
                        <input name="settings[ors_api_key]" value="{{ \App\Services\SettingsService::get('ors_api_key', '') }}" class="mt-1 w-full rounded border-gray-300" placeholder="5b3ce3597851110001cf6248…">
                    </label>
                </div>

                <button class="px-5 py-2 bg-indigo-600 text-white rounded font-medium">Zapisz ustawienia</button>
            </form>

            <div class="mt-8 bg-white shadow-sm rounded-lg p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="font-medium text-gray-700 uppercase text-sm">KSeF — e-faktury</div>
                        <div class="text-sm text-gray-600">Konfiguracja wystawiania faktur w Krajowym Systemie e-Faktur.</div>
                    </div>
                    <a href="{{ route('settings.ksef') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">Konfiguruj KSeF →</a>
                </div>
            </div>

            <div class="mt-4 bg-white shadow-sm rounded-lg p-6">
                <div class="font-medium mb-3 text-gray-700 uppercase text-sm">Twoja publiczna strona i widget WWW</div>

                <p class="text-sm text-gray-600">
                    Adres publicznej strony Twojej firmy:
                    <a href="{{ route('public.page', $organization->slug) }}" target="_blank" class="text-indigo-600 break-all">
                        {{ route('public.page', $organization->slug) }}
                    </a>
                </p>

                <p class="text-sm text-gray-600 mt-4">
                    Aby osadzić formularz zapytania ofertowego na własnej WWW klienta końcowego,
                    wklej poniższy snippet w miejsce gdzie ma się pojawić:
                </p>

                <pre class="mt-2 text-xs bg-gray-900 text-gray-100 p-3 rounded overflow-x-auto select-all"
                >&lt;script src="{{ route('public.widget', ['org' => $organization->slug]) }}"&gt;&lt;/script&gt;</pre>

                <p class="text-xs text-gray-500 mt-2">Limit: 10 zapytań / godzinę / IP. Wbudowany honeypot przeciw botom.</p>
            </div>
        </div>
    </div>
</x-app-layout>
