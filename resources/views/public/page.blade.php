<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $org->name }} — transport koni</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 font-sans">
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-6 py-5 flex justify-between items-center">
            <div class="font-bold text-xl text-indigo-700">{{ $org->name }}</div>
            <div class="text-sm text-gray-600">
                @if ($org->company_phone) tel.: <strong>{{ $org->company_phone }}</strong> @endif
            </div>
        </div>
    </header>

    <section class="max-w-5xl mx-auto px-6 py-16 text-center">
        <h1 class="text-4xl font-bold text-gray-900">{{ $cms['hero_title'] ?? 'Profesjonalny transport koni' }}</h1>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            {{ $cms['hero_subtitle'] ?? 'Bezpieczny, komfortowy transport koni w Polsce i Europie. Wyceń trasę online i otrzymaj ofertę w 24h.' }}
        </p>
    </section>

    @if (! empty($cms['services_text']))
        <section class="max-w-5xl mx-auto px-6 pb-10">
            <div class="bg-white rounded-lg shadow-sm p-6 whitespace-pre-line text-gray-700">{{ $cms['services_text'] }}</div>
        </section>
    @endif

    <section class="max-w-3xl mx-auto px-6 pb-20">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-semibold mb-4">Zapytaj o ofertę</h2>

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('public.page.inquiry', $org->slug) }}" class="grid gap-3">
                @csrf
                <input type="text" name="hp_field" tabindex="-1" autocomplete="off" style="display:none">

                <div class="grid grid-cols-2 gap-3">
                    <input name="client_name" required placeholder="Imię i nazwisko *" class="rounded border-gray-300">
                    <input name="client_email" required type="email" placeholder="E-mail *" class="rounded border-gray-300">
                </div>
                <input name="client_phone" placeholder="Telefon" class="rounded border-gray-300">

                <div class="grid grid-cols-2 gap-3">
                    <input name="from_address" required placeholder="Skąd *" class="rounded border-gray-300">
                    <input name="to_address" required placeholder="Dokąd *" class="rounded border-gray-300">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <input name="transport_date" type="date" class="rounded border-gray-300">
                    <input name="horses_count" type="number" min="1" value="1" class="rounded border-gray-300">
                </div>

                <textarea name="notes" rows="3" placeholder="Dodatkowe informacje" class="rounded border-gray-300"></textarea>

                <button class="px-5 py-2.5 bg-indigo-600 text-white rounded font-medium">Wyślij zapytanie</button>
            </form>
        </div>

        @if (! empty($cms['contact_text']))
            <div class="mt-6 text-center text-sm text-gray-600 whitespace-pre-line">{{ $cms['contact_text'] }}</div>
        @endif
    </section>

    <footer class="py-8 text-center text-xs text-gray-500">
        © {{ date('Y') }} {{ $org->name }} — Powered by GallopTrans
    </footer>
</body>
</html>
