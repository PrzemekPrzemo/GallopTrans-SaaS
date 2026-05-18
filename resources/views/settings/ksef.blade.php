<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Ustawienia KSeF</h2></x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div> @endif

            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded p-4 text-sm">
                <strong>KSeF (Krajowy System e-Faktur)</strong> to centralny rejestr faktur prowadzony przez Ministerstwo Finansów.
                Aby wystawiać e-faktury, wgraj swój certyfikat KSeF i token autoryzacyjny.
                Token uzyskasz po zalogowaniu na
                <a href="https://ksef-test.mf.gov.pl/web/login" class="underline" target="_blank">ksef-test.mf.gov.pl</a> (sandbox)
                lub <a href="https://ksef.mf.gov.pl/" class="underline" target="_blank">ksef.mf.gov.pl</a> (produkcja).
            </div>

            <form method="POST" action="{{ route('settings.ksef.update') }}"
                  enctype="multipart/form-data"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <label class="text-sm">Tryb pracy
                        <select name="ksef_mode" class="mt-1 w-full rounded border-gray-300">
                            <option value="disabled" {{ $organization->ksef_mode === 'disabled' ? 'selected' : '' }}>Wyłączony (faktury tylko w bazie)</option>
                            <option value="test"     {{ $organization->ksef_mode === 'test' ? 'selected' : '' }}>Testowy (sandbox MF)</option>
                            <option value="production" {{ $organization->ksef_mode === 'production' ? 'selected' : '' }}>Produkcja (live)</option>
                        </select>
                    </label>
                    <label class="text-sm">NIP w KSeF (10 cyfr)
                        <input name="ksef_identifier" value="{{ $organization->ksef_identifier ?: $organization->company_nip }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                </div>

                <label class="text-sm block">Token autoryzacyjny KSeF
                    <input type="password" name="ksef_token" placeholder="{{ $has_token ? '••••• (już zapisany — wypełnij tylko jeśli zmieniasz)' : 'wklej token z portalu KSeF' }}"
                           class="mt-1 w-full rounded border-gray-300 font-mono">
                    <span class="text-xs text-gray-500">Token jest szyfrowany w bazie (Laravel Crypt).</span>
                </label>

                <div>
                    <div class="text-sm">Certyfikat (.pem / .pfx / .p12) — opcjonalnie (do podpisu XAdES)
                        @if ($has_cert)
                            <span class="text-green-600 text-xs ml-2">✓ wgrany ({{ basename($organization->ksef_cert_path) }})</span>
                        @endif
                    </div>
                    <input type="file" name="cert_file" accept=".pem,.crt,.pfx,.p12" class="mt-1 block w-full text-sm">
                    @if ($has_cert)
                        <button form="del-cert" class="mt-2 text-xs text-red-600">Usuń obecny certyfikat</button>
                    @endif
                </div>

                <hr class="border-gray-200">

                <div class="grid grid-cols-2 gap-4">
                    <label class="text-sm">Format numeru faktury
                        <input name="invoice_number_format" value="{{ $organization->invoice_number_format }}" class="mt-1 w-full rounded border-gray-300 font-mono">
                        <span class="text-xs text-gray-500">Tokeny: {Y} {YY} {M} {####} {###} {##} {#}</span>
                    </label>
                    <label class="text-sm">Termin płatności (dni)
                        <input name="invoice_payment_due_days" type="number" min="0" max="120" value="{{ $organization->invoice_payment_due_days }}" class="mt-1 w-full rounded border-gray-300">
                    </label>
                </div>

                <button class="px-5 py-2 bg-indigo-600 text-white rounded font-medium">Zapisz ustawienia KSeF</button>
            </form>

            @if ($has_cert)
                <form id="del-cert" method="POST" action="{{ route('settings.ksef.delete-cert') }}" onsubmit="return confirm('Usunąć certyfikat?')">
                    @csrf @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
