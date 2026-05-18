<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Faktura {{ $invoice->number }}</h2>
            <a href="{{ route('invoices.index') }}" class="text-sm text-gray-600">← Powrót</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div> @endif
            @if (session('error'))   <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div> @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-500">Nabywca</div>
                        <div class="font-medium">{{ $invoice->client_company ?: $invoice->client_name }}</div>
                        @if ($invoice->client_nip) <div class="text-sm">NIP: {{ $invoice->client_nip }}</div> @endif
                        @if ($invoice->client_address) <div class="text-sm text-gray-600">{{ $invoice->client_address }}</div> @endif
                        @if ($invoice->client_email)   <div class="text-sm text-gray-600">{{ $invoice->client_email }}</div> @endif
                    </div>
                    <div class="text-right text-sm">
                        <div>Data wystawienia: <strong>{{ $invoice->issued_at->format('Y-m-d') }}</strong></div>
                        <div>Data sprzedaży: {{ $invoice->sold_at->format('Y-m-d') }}</div>
                        <div>Termin płatności: {{ $invoice->payment_due_at?->format('Y-m-d') ?? '—' }}</div>
                        <div>Sposób: {{ $invoice->payment_method }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="font-medium mb-3">Pozycje</div>
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b text-left">
                        <tr><th class="py-2">Opis</th><th class="py-2 text-right">Ilość</th><th class="py-2 text-right">Cena netto</th><th class="py-2 text-right">VAT %</th><th class="py-2 text-right">Suma netto</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($invoice->items as $it)
                            <tr>
                                <td class="py-2">{{ $it->description }}</td>
                                <td class="py-2 text-right">{{ rtrim(rtrim(number_format($it->qty, 2, ',', ' '), '0'), ',') }} {{ $it->unit }}</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->unit_price_net, 2, ',', ' ') }}</td>
                                <td class="py-2 text-right">{{ number_format($it->vat_percent, 0) }}%</td>
                                <td class="py-2 text-right">{{ number_format((float) $it->total_net, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-medium">
                        <tr><td colspan="4" class="py-2 text-right">Netto</td><td class="py-2 text-right">{{ number_format((float) $invoice->subtotal_net, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
                        <tr><td colspan="4" class="py-2 text-right">VAT ({{ number_format($invoice->vat_percent, 0) }}%)</td><td class="py-2 text-right">{{ number_format((float) $invoice->vat_amount, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
                        <tr><td colspan="4" class="py-2 text-right text-lg">Brutto</td><td class="py-2 text-right text-lg">{{ number_format((float) $invoice->total_gross, 2, ',', ' ') }} {{ $invoice->currency }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="font-medium mb-3">KSeF</div>
                <div class="text-sm mb-3">
                    Status: <strong>{{ $invoice->ksef_status }}</strong>
                    @if ($invoice->ksef_reference) · referencja: <span class="font-mono">{{ $invoice->ksef_reference }}</span> @endif
                    @if ($invoice->ksef_sent_at)   · wysłana: {{ $invoice->ksef_sent_at->format('Y-m-d H:i') }} @endif
                </div>

                @if (in_array($invoice->ksef_status, ['draft', 'rejected']))
                    <form method="POST" action="{{ route('invoices.ksef-send', $invoice) }}"
                          onsubmit="return confirm('Wysłać fakturę do KSeF?')">
                        @csrf
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Wyślij do KSeF</button>
                    </form>
                @elseif ($invoice->ksef_status === 'manual')
                    <div class="text-sm text-gray-600">
                        KSeF jest wyłączony dla tej organizacji.
                        <a href="{{ route('settings.ksef') }}" class="text-indigo-600">→ Włącz w ustawieniach KSeF</a>
                    </div>
                @endif

                @if ($invoice->ksef_response)
                    <details class="mt-3 text-xs text-gray-500">
                        <summary class="cursor-pointer">Surowa odpowiedź KSeF</summary>
                        <pre class="bg-gray-900 text-gray-100 p-3 rounded mt-2 overflow-x-auto">{{ json_encode($invoice->ksef_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>

            {{-- Korekta --}}
            @if ($invoice->type === 'invoice')
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="font-medium mb-2">Faktura korygująca</div>

                    @if ($invoice->correctedBy->isNotEmpty())
                        <div class="text-sm text-gray-700 mb-2">
                            Już wystawione korekty:
                            @foreach ($invoice->correctedBy as $c)
                                <a href="{{ route('invoices.show', $c) }}" class="text-indigo-600 font-mono mx-1">{{ $c->number }}</a>
                            @endforeach
                        </div>
                    @endif

                    <details>
                        <summary class="cursor-pointer text-sm text-indigo-600">+ Wystaw korektę</summary>
                        <form method="POST" action="{{ route('invoices.correct', $invoice) }}" class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            @csrf
                            <label class="col-span-2">Powód korekty *
                                <input name="reason" required class="mt-1 w-full rounded border-gray-300" placeholder="np. zmiana kwoty po reklamacji">
                            </label>
                            <label>Nowa kwota netto
                                <input name="subtotal_net" type="number" step="0.01" value="{{ $invoice->subtotal_net }}" class="mt-1 w-full rounded border-gray-300">
                            </label>
                            <label>Nowa kwota brutto
                                <input name="total_gross" type="number" step="0.01" value="{{ $invoice->total_gross }}" class="mt-1 w-full rounded border-gray-300">
                            </label>
                            <label class="col-span-2">Notatki
                                <textarea name="notes" rows="2" class="mt-1 w-full rounded border-gray-300"></textarea>
                            </label>
                            <button class="col-span-2 px-4 py-2 bg-emerald-600 text-white rounded">Wystaw fakturę korygującą</button>
                        </form>
                    </details>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded p-4 text-sm">
                    To jest <strong>faktura korygująca</strong> do faktury
                    @if ($invoice->correctsInvoice)
                        <a href="{{ route('invoices.show', $invoice->correctsInvoice) }}" class="text-indigo-600 font-mono">{{ $invoice->correctsInvoice->number }}</a>
                    @endif
                    @if ($invoice->correction_reason) — powód: <em>{{ $invoice->correction_reason }}</em> @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
