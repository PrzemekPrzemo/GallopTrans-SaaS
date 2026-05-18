<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GallopTrans — SaaS dla transportu koni</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gradient-to-b from-indigo-50 via-white to-white font-sans">
    <nav class="absolute top-0 inset-x-0 px-6 py-4 flex justify-between items-center">
        <div class="font-bold text-xl text-indigo-700">GallopTrans</div>
        <div class="space-x-4 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">Pulpit</a>
            @else
                <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-700">Zaloguj się</a>
                <a href="{{ route('register') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">Wypróbuj za darmo</a>
            @endauth
        </div>
    </nav>

    <section class="max-w-5xl mx-auto px-6 pt-32 pb-20 text-center">
        <h1 class="text-5xl font-bold text-gray-900 leading-tight">
            Kalkulator tras i ofertowanie<br>dla firm transportujących konie
        </h1>
        <p class="mt-6 text-lg text-gray-600 max-w-2xl mx-auto">
            Wycena trasy w 30 sekund. Routing pojazdów ciężarowych (HGV), automatyczne opłaty drogowe,
            generowanie ofert PDF, publiczny link do akceptacji przez klienta. Wszystko w jednym SaaS.
        </p>
        <div class="mt-10 flex justify-center gap-4">
            <a href="{{ route('register') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                Rozpocznij 14 dni za darmo →
            </a>
            <a href="#features" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Zobacz funkcje
            </a>
        </div>
    </section>

    <section id="features" class="max-w-5xl mx-auto px-6 py-16 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-3xl">🗺️</div>
            <div class="font-semibold mt-3">Routing HGV</div>
            <div class="text-sm text-gray-600 mt-1">Openrouteservice z restrykcjami wagi, wysokości i osi. Automatyczne wykrywanie autostrad i opłat drogowych.</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-3xl">💰</div>
            <div class="font-semibold mt-3">Pełna logika wyceny</div>
            <div class="text-sm text-gray-600 mt-1">Paliwo, dopłaty za konie, postoje, marża, VAT, kurs EUR z NBP, zaokrąglenie do pełnych 10 zł.</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-3xl">📄</div>
            <div class="font-semibold mt-3">Oferty i publiczna akceptacja</div>
            <div class="text-sm text-gray-600 mt-1">PDF, e-mail do klienta, link do podpisu online — bez wymagania konta klienta.</div>
        </div>
    </section>

    <section id="how" class="bg-gray-50 py-16">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Jak to działa</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-700 rounded-full mx-auto flex items-center justify-center text-xl font-bold">1</div>
                    <div class="font-semibold mt-3">Rejestracja + 14 dni trialu</div>
                    <div class="text-sm text-gray-600 mt-2">Bez karty kredytowej. Onboarding w 30 sekund — podajesz nazwę firmy i NIP.</div>
                </div>
                <div>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-700 rounded-full mx-auto flex items-center justify-center text-xl font-bold">2</div>
                    <div class="font-semibold mt-3">Wyceniasz trasy</div>
                    <div class="text-sm text-gray-600 mt-2">Wpisujesz adresy, ustawiasz parametry pojazdu i koni — wycena gotowa w 30 sekund z mapą i opłatami drogowymi.</div>
                </div>
                <div>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-700 rounded-full mx-auto flex items-center justify-center text-xl font-bold">3</div>
                    <div class="font-semibold mt-3">Wysyłasz ofertę, fakturujesz</div>
                    <div class="text-sm text-gray-600 mt-2">PDF + e-mail do klienta + publiczny link do akceptacji. Po akceptacji wystawiasz fakturę przez KSeF.</div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-16">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Cennik</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6 border">
                    <div class="font-semibold text-lg">Starter</div>
                    <div class="text-3xl font-bold mt-2">99 zł<span class="text-sm font-normal text-gray-500">/mies.</span></div>
                    <ul class="mt-4 space-y-1 text-sm text-gray-700">
                        <li>✓ 50 wycen / mies.</li><li>✓ 1 użytkownik</li><li>✓ PDF + e-mail</li>
                    </ul>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6 ring-2 ring-indigo-500">
                    <div class="text-xs text-indigo-600 font-semibold">NAJPOPULARNIEJSZY</div>
                    <div class="font-semibold text-lg">Pro</div>
                    <div class="text-3xl font-bold mt-2">249 zł<span class="text-sm font-normal text-gray-500">/mies.</span></div>
                    <ul class="mt-4 space-y-1 text-sm text-gray-700">
                        <li>✓ Nieograniczone wyceny</li><li>✓ Do 5 użytkowników</li><li>✓ Faktury KSeF</li>
                        <li>✓ Widget WWW + iCal</li><li>✓ Publiczna strona firmowa</li>
                    </ul>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6 border">
                    <div class="font-semibold text-lg">Business</div>
                    <div class="text-3xl font-bold mt-2">599 zł<span class="text-sm font-normal text-gray-500">/mies.</span></div>
                    <ul class="mt-4 space-y-1 text-sm text-gray-700">
                        <li>✓ Wszystko z Pro</li><li>✓ Nieograniczeni userzy</li>
                        <li>✓ Custom branding</li><li>✓ Dedykowany opiekun</li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-8">
                <a href="{{ route('register') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium">14 dni za darmo, bez karty →</a>
            </div>
        </div>
    </section>

    <footer class="py-10 text-center text-sm text-gray-500">
        © {{ date('Y') }} GallopTrans · SaaS dla branży transportu zwierząt
    </footer>
</body>
</html>
