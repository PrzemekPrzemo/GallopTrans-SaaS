<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Oferta {{ $quote->number }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('quotes.pdf', $quote) }}" target="_blank"
                   class="px-3 py-1.5 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50">📄 PDF</a>
                <a href="{{ route('quotes.index') }}" class="px-3 py-1.5 text-sm text-gray-600">← Powrót</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-sm text-gray-500">Klient</div>
                        <div class="text-lg font-semibold">{{ $quote->client_name }}</div>
                        @if ($quote->client_company) <div class="text-sm text-gray-600">{{ $quote->client_company }} {{ $quote->client_nip ? ' · NIP ' . $quote->client_nip : '' }}</div> @endif
                        @if ($quote->client_email) <div class="text-sm text-gray-600">{{ $quote->client_email }}</div> @endif
                        @if ($quote->client_phone) <div class="text-sm text-gray-600">{{ $quote->client_phone }}</div> @endif
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="text-lg font-semibold">{{ $quote->status }}</div>
                        @if ($quote->valid_until)
                            <div class="text-sm text-gray-500 mt-2">Ważna do</div>
                            <div>{{ $quote->valid_until->format('Y-m-d') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-500">Skąd</div>
                        <div>{{ $quote->from_address }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Dokąd</div>
                        <div>{{ $quote->to_address }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Dystans</div>
                        <div>{{ number_format((float) $quote->distance_km, 2, ',', ' ') }} km · {{ intdiv($quote->duration_min, 60) }} h {{ $quote->duration_min % 60 }} min</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Tryb</div>
                        <div>{{ $quote->trip_mode }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Konie</div>
                        <div>{{ $quote->horses_count }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Data transportu</div>
                        <div>{{ $quote->transport_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="font-medium mb-3">Pozycje</div>
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b text-left">
                        <tr><th class="py-2">Opis</th><th class="py-2 text-right">Ilość</th><th class="py-2 text-right">Cena netto</th><th class="py-2 text-right">Suma netto</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($quote->items as $it)
                            <tr>
                                <td class="py-2">{{ $it->description }}</td>
                                <td class="py-2 text-right">{{ rtrim(rtrim(number_format($it->qty, 2, ',', ' '), '0'), ',') }} {{ $it->unit }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->unit_price_net, 2, ',', ' ') }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->total_net, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-medium">
                        <tr><td colspan="3" class="py-2 text-right">Netto</td><td class="py-2 text-right">{{ number_format((float) $quote->subtotal_net, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                        <tr><td colspan="3" class="py-2 text-right">VAT ({{ number_format((float) $quote->vat_percent, 0) }}%)</td><td class="py-2 text-right">{{ number_format((float) $quote->vat_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                        <tr><td colspan="3" class="py-2 text-right text-lg">Brutto</td><td class="py-2 text-right text-lg">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Wysyłka mailem --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="font-medium mb-3">Wyślij ofertę mailem</div>
                    <form method="POST" action="{{ route('quotes.send', $quote) }}" class="space-y-2">
                        @csrf
                        <input type="email" name="to" value="{{ $quote->client_email }}" required
                               class="w-full rounded border-gray-300" placeholder="adres e-mail klienta">
                        <textarea name="message" rows="3" class="w-full rounded border-gray-300"
                                  placeholder="Treść maila (opcjonalnie)"></textarea>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Wyślij ofertę (PDF w załączniku)</button>
                    </form>
                </div>

                {{-- Płatności --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="font-medium mb-1">Wpłaty</div>
                    @php
                        $paid = $quote->totalPaid();
                        $balance = $quote->balance();
                    @endphp
                    <div class="text-sm text-gray-600 mb-3">
                        Zapłacono: <strong>{{ number_format($paid, 2, ',', ' ') }} {{ $quote->currency }}</strong> ·
                        Pozostało: <strong class="{{ $balance > 0 ? 'text-amber-700' : 'text-green-700' }}">{{ number_format($balance, 2, ',', ' ') }} {{ $quote->currency }}</strong>
                    </div>

                    @if ($quote->payments->count() > 0)
                        <ul class="text-sm border-t border-gray-100 mb-3">
                            @foreach ($quote->payments as $p)
                                <li class="flex justify-between py-1 border-b border-gray-50">
                                    <span>{{ $p->paid_at->format('Y-m-d') }} · {{ $p->payment_type }} · {{ $p->payment_method }}</span>
                                    <span class="font-mono">{{ number_format((float) $p->amount_gross, 2, ',', ' ') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('quotes.payments.store', $quote) }}" class="grid grid-cols-2 gap-2 text-sm">
                        @csrf
                        <input type="number" step="0.01" name="amount_gross" required placeholder="Kwota brutto"
                               class="rounded border-gray-300 col-span-2" value="{{ $balance > 0 ? $balance : '' }}">
                        <input type="date" name="paid_at" required value="{{ now()->toDateString() }}"
                               class="rounded border-gray-300">
                        <select name="payment_type" class="rounded border-gray-300">
                            <option value="full">pełna</option>
                            <option value="advance">zaliczka</option>
                            <option value="final">końcowa</option>
                            <option value="other">inna</option>
                        </select>
                        <select name="payment_method" class="rounded border-gray-300 col-span-2">
                            <option value="transfer">przelew</option>
                            <option value="cash">gotówka</option>
                            <option value="card">karta</option>
                            <option value="other">inne</option>
                        </select>
                        <input type="text" name="reference" placeholder="Nr referencyjny / faktura"
                               class="rounded border-gray-300 col-span-2">
                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white rounded col-span-2">+ Dodaj wpłatę</button>
                    </form>
                </div>
            </div>

            {{-- Faktura — pokazujemy tylko gdy oferta zaakceptowana --}}
            @if ($quote->status === 'accepted')
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="font-medium mb-2">Faktura VAT</div>
                    @if ($quote->invoice)
                        <div class="flex items-center justify-between">
                            <div>
                                Faktura <a class="text-indigo-600 font-mono" href="{{ route('invoices.show', $quote->invoice) }}">{{ $quote->invoice->number }}</a>
                                · KSeF: <span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $quote->invoice->ksef_status }}</span>
                            </div>
                            <a class="text-indigo-600 text-sm" href="{{ route('invoices.show', $quote->invoice) }}">→ Otwórz</a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('invoices.from-quote', $quote) }}" onsubmit="return confirm('Wystawić fakturę z tej oferty?')">
                            @csrf
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded">Wystaw fakturę</button>
                        </form>
                    @endif
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6 text-sm text-gray-600">
                Link publiczny dla klienta:
                <a class="text-indigo-600 break-all" href="{{ route('quotes.public', $quote->public_token) }}">{{ route('quotes.public', $quote->public_token) }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
