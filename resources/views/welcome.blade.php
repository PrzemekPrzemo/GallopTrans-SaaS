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

    <footer class="py-10 text-center text-sm text-gray-500">
        © {{ date('Y') }} GallopTrans · SaaS dla branży transportu zwierząt
    </footer>
</body>
</html>
