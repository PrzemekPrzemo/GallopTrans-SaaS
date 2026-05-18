<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Kalkulator tras transportu koni</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <style>
            #map { height: 460px; border-radius: 0.5rem; }
            .ac-list { position:absolute; z-index:50; top:100%; left:0; right:0; background:#fff;
                       border:1px solid #d1d5db; border-radius:0.375rem; box-shadow:0 2px 6px rgba(0,0,0,.08);
                       max-height:260px; overflow:auto; }
            .ac-item { padding:.5rem .75rem; cursor:pointer; font-size:.875rem; border-bottom:1px solid #f3f4f6; }
            .ac-item:hover { background:#f3f4f6; }
        </style>
    @endpush

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEWY PANEL — formularz --}}
            <div class="lg:col-span-2 space-y-4">

                @if (! $ors_configured)
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded p-3 text-sm">
                        ⚠️ Brak klucza ORS. Dodaj w panelu ustawień, inaczej autocomplete i wyznaczanie trasy nie zadziała.
                    </div>
                @endif

                <div class="bg-white rounded-lg shadow-sm p-5 space-y-4">
                    <div class="font-medium">1. Trasa</div>

                    <div class="relative">
                        <label class="block text-sm text-gray-700">Skąd</label>
                        <input id="from_address" type="text" autocomplete="off"
                               value="{{ $prefill['from_address'] }}"
                               class="mt-1 w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
                        <div id="from_list" class="ac-list hidden"></div>
                    </div>

                    <div class="relative">
                        <label class="block text-sm text-gray-700">Dokąd</label>
                        <input id="to_address" type="text" autocomplete="off"
                               value="{{ $prefill['to_address'] }}"
                               class="mt-1 w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
                        <div id="to_list" class="ac-list hidden"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-700">Tryb trasy</label>
                            <select id="trip_mode" class="mt-1 w-full rounded border-gray-300">
                                <option value="one_way">Jednorazowo (tylko tam)</option>
                                <option value="round_trip" {{ $defaults['round_trip'] ? 'selected' : '' }}>W obie strony (tam i z powrotem)</option>
                                <option value="return_home">Tam + powrót bezpośredni (return home)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Data transportu</label>
                            <input id="transport_date" type="date" value="{{ $prefill['transport_date'] }}"
                                   class="mt-1 w-full rounded border-gray-300" />
                        </div>
                    </div>

                    <button type="button" id="btn_route"
                            class="px-4 py-2 bg-indigo-600 text-white rounded font-medium hover:bg-indigo-700 disabled:opacity-50">
                        Wyznacz trasę
                    </button>
                    <span id="route_info" class="ml-3 text-sm text-gray-600"></span>

                    <div id="map" class="mt-4"></div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 space-y-4">
                    <div class="font-medium">2. Konie & pojazd</div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm text-gray-700">Liczba koni</label>
                            <input id="horses_count" type="number" min="1" max="20"
                                   value="{{ $defaults['horses_count'] }}"
                                   class="mt-1 w-full rounded border-gray-300" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm text-gray-700">Pojazd</label>
                            <select id="vehicle_id" class="mt-1 w-full rounded border-gray-300">
                                @foreach ($vehicles as $v)
                                    <option value="{{ $v->id }}"
                                            data-consumption="{{ $v->fuel_consumption }}"
                                            data-capacity="{{ $v->horse_capacity }}"
                                            {{ $v->id == $defaults['vehicle_id'] ? 'selected' : '' }}>
                                        {{ $v->name }} ({{ $v->fuel_consumption }} l/100, {{ $v->horse_capacity }} koni)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 space-y-4">
                    <div class="font-medium">3. Parametry wyceny</div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <label>Stawka km
                            <input id="base_rate_per_km" type="number" step="0.01" value="{{ $defaults['base_rate_per_km'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>Spalanie (l/100)
                            <input id="fuel_consumption" type="number" step="0.1" value="{{ $defaults['fuel_consumption'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>Cena paliwa
                            <input id="fuel_price" type="number" step="0.001" value="{{ $defaults['fuel_price'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>Narzut %
                            <input id="surcharge_percent" type="number" step="0.1" value="{{ $defaults['surcharge_percent'] }}" class="mt-1 w-full rounded border-gray-300"/></label>

                        <label>Dopłata za konia
                            <input id="extra_horse_fee" type="number" step="0.01" value="{{ $defaults['extra_horse_fee'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>Opłaty drogowe
                            <input id="toll_cost" type="number" step="0.01" value="0" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>Min. kwota
                            <input id="min_quote_amount" type="number" step="0.01" value="{{ $defaults['min_quote_amount'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label>VAT %
                            <input id="vat_percent" type="number" step="0.1" value="{{ $defaults['vat_percent'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                    </div>

                    <button type="button" id="btn_tolls"
                            class="text-sm px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded">
                        Oszacuj opłaty drogowe automatycznie (ORS)
                    </button>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 space-y-4">
                    <div class="font-medium">4. Klient (opcjonalne — wymagane do zapisu oferty)</div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm">Imię i nazwisko
                            <input id="client_name" type="text" value="{{ $prefill['client_name'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label class="text-sm">E-mail
                            <input id="client_email" type="email" value="{{ $prefill['client_email'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label class="text-sm">Telefon
                            <input id="client_phone" type="tel" value="{{ $prefill['client_phone'] }}" class="mt-1 w-full rounded border-gray-300"/></label>
                        <label class="text-sm">Firma / NIP
                            <input id="client_company" type="text" class="mt-1 w-full rounded border-gray-300" placeholder="Nazwa firmy"/></label>
                    </div>
                </div>
            </div>

            {{-- PRAWY PANEL — live preview --}}
            <div>
                <div class="bg-white rounded-lg shadow-sm p-5 sticky top-4">
                    <div class="text-sm text-gray-500 uppercase tracking-wide">Wycena na żywo</div>
                    <div class="text-4xl font-bold mt-1" id="preview_total">— zł</div>
                    <div class="text-sm text-gray-500" id="preview_subtotal">netto: —</div>
                    <div class="text-sm text-gray-500" id="preview_vat">VAT: —</div>

                    <div class="mt-4 border-t pt-3" id="preview_items">
                        <div class="text-gray-400 text-sm">Wybierz trasę, aby zobaczyć szczegóły.</div>
                    </div>

                    <button id="btn_save" type="button" disabled
                            class="mt-4 w-full px-4 py-2.5 bg-emerald-600 text-white rounded font-medium hover:bg-emerald-700 disabled:opacity-50">
                        Zapisz jako ofertę →
                    </button>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    (() => {
        const csrf = '{{ csrf_token() }}';
        const map = L.map('map').setView([52.0, 19.0], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap',
        }).addTo(map);

        let fromPoint = null, toPoint = null;
        let fromMarker = null, toMarker = null;
        let routeLayer = null;
        let distanceKm = 0, durationMin = 0;

        const $ = (id) => document.getElementById(id);
        const fmt = (n) => Number(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

        async function api(url, body) {
            const r = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body || {}),
            });
            return r.json();
        }

        // ===== Autocomplete (Pelias przez nasz endpoint) =====
        function bindAutocomplete(inputId, listId, onPick) {
            const input = $(inputId), list = $(listId);
            let t = null;
            input.addEventListener('input', () => {
                clearTimeout(t);
                const q = input.value.trim();
                if (q.length < 3) { list.classList.add('hidden'); return; }
                t = setTimeout(async () => {
                    const r = await fetch(`{{ route('calculator.geocode') }}?q=` + encodeURIComponent(q));
                    const data = await r.json();
                    list.innerHTML = '';
                    if (!data.results || data.results.length === 0) { list.classList.add('hidden'); return; }
                    for (const it of data.results) {
                        const div = document.createElement('div');
                        div.className = 'ac-item';
                        div.textContent = it.label;
                        div.onclick = () => { input.value = it.label; list.classList.add('hidden'); onPick(it); };
                        list.appendChild(div);
                    }
                    list.classList.remove('hidden');
                }, 250);
            });
            input.addEventListener('blur', () => setTimeout(() => list.classList.add('hidden'), 200));
        }

        bindAutocomplete('from_address', 'from_list', (it) => {
            fromPoint = { lat: it.lat, lng: it.lng };
            if (fromMarker) map.removeLayer(fromMarker);
            fromMarker = L.marker([it.lat, it.lng], { title: 'Skąd' }).addTo(map);
            map.setView([it.lat, it.lng], 9);
        });
        bindAutocomplete('to_address', 'to_list', (it) => {
            toPoint = { lat: it.lat, lng: it.lng };
            if (toMarker) map.removeLayer(toMarker);
            toMarker = L.marker([it.lat, it.lng], { title: 'Dokąd' }).addTo(map);
            if (fromPoint) map.fitBounds([[fromPoint.lat, fromPoint.lng], [it.lat, it.lng]], { padding: [40, 40] });
        });

        // ===== Routing =====
        $('btn_route').addEventListener('click', async () => {
            if (!fromPoint || !toPoint) { alert('Wybierz oba punkty z autocomplete.'); return; }
            $('btn_route').disabled = true;
            $('route_info').textContent = 'Liczę trasę…';
            const r = await api(`{{ route('calculator.route') }}`, {
                points: [fromPoint, toPoint],
                vehicle_id: Number($('vehicle_id').value),
            });
            $('btn_route').disabled = false;
            if (!r.ok) { $('route_info').textContent = 'Błąd: ' + r.error; return; }
            distanceKm = r.distance_km;
            durationMin = r.duration_min;
            $('route_info').innerHTML = `<strong>${fmt(distanceKm)} km</strong> · ${Math.floor(durationMin/60)} h ${durationMin % 60} min`;
            if (routeLayer) map.removeLayer(routeLayer);
            if (r.geometry) {
                routeLayer = L.geoJSON(r.geometry, { style: { color: '#4f46e5', weight: 5 } }).addTo(map);
                map.fitBounds(routeLayer.getBounds(), { padding: [30, 30] });
            }
            recalc();
        });

        // ===== Toll estimate =====
        $('btn_tolls').addEventListener('click', async () => {
            if (!fromPoint || !toPoint) { alert('Najpierw wyznacz trasę.'); return; }
            const r = await api(`{{ route('calculator.estimate-tolls') }}`, {
                points: [fromPoint, toPoint],
                trip_mode: $('trip_mode').value,
                vehicle_id: Number($('vehicle_id').value),
            });
            if (!r.ok) { alert('Błąd: ' + r.error); return; }
            $('toll_cost').value = r.toll_cost;
            recalc();
        });

        // ===== Live calc =====
        async function recalc() {
            if (!distanceKm) return;
            const body = {
                distance_km: distanceKm,
                trip_mode: $('trip_mode').value,
                horses_count: Number($('horses_count').value) || 1,
                fuel_consumption: Number($('fuel_consumption').value) || 0,
                fuel_price: Number($('fuel_price').value) || 0,
                base_rate_per_km: Number($('base_rate_per_km').value) || 0,
                surcharge_percent: Number($('surcharge_percent').value) || 0,
                extra_horse_fee: Number($('extra_horse_fee').value) || 0,
                toll_cost: Number($('toll_cost').value) || 0,
                min_quote_amount: Number($('min_quote_amount').value) || 0,
                vat_percent: Number($('vat_percent').value) || 0,
                currency: '{{ $defaults['currency'] }}',
                exchange_rate: {{ $defaults['exchange_rate'] }},
            };
            const r = await api(`{{ route('calculator.calculate') }}`, body);
            if (!r.ok) { return; }
            const res = r.result;
            $('preview_total').textContent    = fmt(res.total_gross) + ' ' + res.currency;
            $('preview_subtotal').textContent = 'netto: ' + fmt(res.subtotal_net) + ' ' + res.currency;
            $('preview_vat').textContent      = 'VAT: ' + fmt(res.vat_amount) + ' ' + res.currency;
            const list = $('preview_items');
            list.innerHTML = '<div class="text-xs text-gray-500 uppercase mb-1">Pozycje</div>';
            for (const it of res.items) {
                const row = document.createElement('div');
                row.className = 'text-sm flex justify-between gap-2 py-0.5';
                row.innerHTML = `<span class="text-gray-700 truncate">${it.description}</span><span class="font-mono">${fmt(it.total_net)}</span>`;
                list.appendChild(row);
            }
            $('btn_save').disabled = !($('client_name').value && distanceKm > 0);
        }

        ['change', 'input'].forEach(ev => {
            ['horses_count','vehicle_id','trip_mode','base_rate_per_km','fuel_consumption','fuel_price',
             'surcharge_percent','extra_horse_fee','toll_cost','min_quote_amount','vat_percent','client_name']
            .forEach(id => $(id).addEventListener(ev, recalc));
        });

        // ===== Save as quote =====
        $('btn_save').addEventListener('click', async () => {
            if (!$('client_name').value.trim()) { alert('Podaj imię klienta.'); return; }
            $('btn_save').disabled = true;
            const r = await api(`{{ route('calculator.save-as-quote') }}`, {
                distance_km: distanceKm,
                duration_min: durationMin,
                trip_mode: $('trip_mode').value,
                from_address: $('from_address').value, from_lat: fromPoint?.lat, from_lng: fromPoint?.lng,
                to_address:   $('to_address').value,   to_lat:   toPoint?.lat,   to_lng:   toPoint?.lng,
                transport_date: $('transport_date').value || null,
                horses_count: Number($('horses_count').value) || 1,
                vehicle_id: Number($('vehicle_id').value),
                fuel_consumption: Number($('fuel_consumption').value),
                fuel_price: Number($('fuel_price').value),
                base_rate_per_km: Number($('base_rate_per_km').value),
                surcharge_percent: Number($('surcharge_percent').value),
                extra_horse_fee: Number($('extra_horse_fee').value),
                toll_cost: Number($('toll_cost').value),
                min_quote_amount: Number($('min_quote_amount').value),
                vat_percent: Number($('vat_percent').value),
                currency: '{{ $defaults['currency'] }}',
                exchange_rate: {{ $defaults['exchange_rate'] }},
                client_name: $('client_name').value,
                client_email: $('client_email').value,
                client_phone: $('client_phone').value,
                client_company: $('client_company').value,
            });
            if (r.ok) {
                window.location = r.redirect;
            } else {
                $('btn_save').disabled = false;
                alert('Błąd zapisu: ' + (r.error || JSON.stringify(r.errors)));
            }
        });

        // ===== Klik na mapie → zmień from/to =====
        let pickMode = 'from';
        map.on('click', async (e) => {
            const r = await api(`{{ route('calculator.reverse-geocode') }}`, { lat: e.latlng.lat, lng: e.latlng.lng });
            if (!r.ok) return;
            if (pickMode === 'from') {
                fromPoint = { lat: r.result.lat, lng: r.result.lng };
                $('from_address').value = r.result.label;
                if (fromMarker) map.removeLayer(fromMarker);
                fromMarker = L.marker([fromPoint.lat, fromPoint.lng]).addTo(map);
                pickMode = 'to';
            } else {
                toPoint = { lat: r.result.lat, lng: r.result.lng };
                $('to_address').value = r.result.label;
                if (toMarker) map.removeLayer(toMarker);
                toMarker = L.marker([toPoint.lat, toPoint.lng]).addTo(map);
                pickMode = 'from';
            }
        });
    })();
    </script>
    @endpush
</x-app-layout>
