<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800">👑 {{ $org->name }}</h2>
            <a href="{{ route('admin.organizations') }}" class="text-sm text-gray-600">← Wszystkie firmy</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><strong>Slug:</strong> {{ $org->slug }}</div>
                    <div><strong>NIP:</strong> {{ $org->company_nip ?? '—' }}</div>
                    <div><strong>E-mail:</strong> {{ $org->company_email ?? '—' }}</div>
                    <div><strong>Telefon:</strong> {{ $org->company_phone ?? '—' }}</div>
                    <div><strong>Plan:</strong> {{ $org->plan }}</div>
                    <div><strong>Trial do:</strong> {{ $org->trial_ends_at?->format('Y-m-d') ?? '—' }}</div>
                    <div><strong>Stripe ID:</strong> <span class="font-mono text-xs">{{ $org->stripe_id ?? '—' }}</span></div>
                    <div><strong>KSeF tryb:</strong> {{ $org->ksef_mode }}</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Użytkownicy ({{ $org->users->count() }})</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @foreach ($org->users as $u)
                            <tr>
                                <td class="px-4 py-2">{{ $u->name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $u->email }}</td>
                                <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $u->role }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Subskrypcje</div>
                @if ($org->subscriptions->isEmpty())
                    <div class="px-5 py-4 text-gray-500 text-sm">Brak subskrypcji Stripe.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr><th class="px-4 py-2">Plan (Stripe)</th><th>Status</th><th>Trial do</th><th>Kończy się</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($org->subscriptions as $s)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs">{{ $s->stripe_price }}</td>
                                    <td class="px-4 py-2">{{ $s->stripe_status }}</td>
                                    <td class="px-4 py-2">{{ $s->trial_ends_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $s->ends_at?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                Łącznie wycen wystawionych: <strong>{{ $quotesCount }}</strong>
            </div>
        </div>
    </div>
</x-app-layout>
