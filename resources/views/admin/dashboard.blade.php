<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">👑 Admin SaaS-a — Pulpit</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Organizacje (łącznie)</div>
                    <div class="text-3xl font-semibold">{{ $orgs_total }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Płacące</div>
                    <div class="text-3xl font-semibold text-emerald-700">{{ $orgs_paying }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Trial</div>
                    <div class="text-3xl font-semibold text-amber-700">{{ $orgs_trial }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">MRR (szac.)</div>
                    <div class="text-3xl font-semibold">{{ number_format($mrr_pln, 0, ',', ' ') }} zł</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Użytkownicy (łącznie)</div>
                    <div class="text-3xl font-semibold">{{ $users_total }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Wyceny (ten miesiąc)</div>
                    <div class="text-3xl font-semibold">{{ $quotes_month }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Churn</div>
                    <div class="text-3xl font-semibold text-red-700">{{ $orgs_cancelled }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Konwersja trial→paid</div>
                    <div class="text-3xl font-semibold">
                        {{ ($orgs_paying + $orgs_trial) > 0 ? round($orgs_paying / ($orgs_paying + $orgs_trial) * 100) . '%' : '—' }}
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b flex justify-between">
                    <span class="font-medium">10 ostatnio zarejestrowanych firm</span>
                    <a href="{{ route('admin.organizations') }}" class="text-sm text-indigo-600">Wszystkie firmy →</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr><th class="px-4 py-2">Firma</th><th>Plan / status</th><th>Trial do</th><th>Utworzono</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($recent as $org)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2"><a href="{{ route('admin.organization', $org) }}" class="text-indigo-600">{{ $org->name }}</a></td>
                                <td class="px-4 py-2">{{ $org->subscribed('default') ? '✓ płacący' : ($org->trial_ends_at && $org->trial_ends_at->isFuture() ? 'trial' : '—') }}</td>
                                <td class="px-4 py-2">{{ $org->trial_ends_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $org->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
