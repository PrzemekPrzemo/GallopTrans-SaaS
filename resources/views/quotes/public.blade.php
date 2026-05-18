<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Oferta {{ $quote->number }} — {{ $quote->organization->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-3xl mx-auto py-10 px-4 space-y-6">

        <div class="bg-white rounded-lg shadow p-8">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-2xl font-bold">Oferta {{ $quote->number }}</div>
                    <div class="text-gray-500 text-sm mt-1">{{ $quote->organization->name }}</div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-indigo-600">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</div>
                    <div class="text-sm text-gray-500">brutto</div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><strong>Skąd:</strong> {{ $quote->from_address }}</div>
                <div><strong>Dokąd:</strong> {{ $quote->to_address }}</div>
                <div><strong>Dystans:</strong> {{ number_format((float) $quote->distance_km, 2, ',', ' ') }} km</div>
                <div><strong>Konie:</strong> {{ $quote->horses_count }}</div>
                <div><strong>Data:</strong> {{ $quote->transport_date?->format('Y-m-d') ?? '—' }}</div>
                <div><strong>Ważna do:</strong> {{ $quote->valid_until?->format('Y-m-d') ?? '—' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-8">
            <div class="font-medium mb-3">Pozycje</div>
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    @foreach ($quote->items as $it)
                        <tr>
                            <td class="py-2">{{ $it->description }}</td>
                            <td class="py-2 text-right">{{ number_format((float) $it->total_net, 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-medium"><td class="py-2">Netto razem</td><td class="py-2 text-right">{{ number_format((float) $quote->subtotal_net, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                    <tr><td class="py-2">VAT</td><td class="py-2 text-right">{{ number_format((float) $quote->vat_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                    <tr class="font-bold text-lg"><td class="py-2">Do zapłaty</td><td class="py-2 text-right">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
                </tbody>
            </table>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded">{{ session('warning') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="text-center space-y-3">
            @if ($quote->status === 'accepted')
                <div class="inline-block bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded">
                    ✓ Oferta zaakceptowana {{ $quote->accepted_at?->format('Y-m-d') }}
                </div>
            @elseif (! in_array($quote->status, ['rejected', 'expired', 'cancelled']))
                <form method="POST" action="{{ route('quotes.public.accept', $quote->public_token) }}"
                      onsubmit="return confirm('Akceptujesz tę ofertę i zlecasz transport?')">
                    @csrf
                    <button class="inline-block px-6 py-3 bg-emerald-600 text-white rounded font-medium text-lg">
                        ✓ Akceptuję ofertę
                    </button>
                </form>
            @endif

            <div>
                <a href="{{ route('quotes.public.pdf', $quote->public_token) }}"
                   class="inline-block px-5 py-2 bg-indigo-600 text-white rounded font-medium">Pobierz ofertę (PDF)</a>
            </div>
        </div>

        <div class="text-center text-xs text-gray-500">Dziękujemy za zaufanie — {{ $quote->organization->name }}</div>
    </div>
</body>
</html>
