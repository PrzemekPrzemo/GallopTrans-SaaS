<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Plany subskrypcji</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            @if ($organization->trial_ends_at && $organization->trial_ends_at->isFuture())
                <div class="bg-amber-50 border border-amber-200 rounded p-4 mb-6">
                    Trial kończy się {{ $organization->trial_ends_at->format('Y-m-d') }} ({{ $organization->trial_ends_at->diffForHumans() }}). Wybierz plan żeby zachować dostęp.
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded mb-6">{{ session('warning') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($plans as $key => $plan)
                    <div class="bg-white rounded-lg shadow-sm p-6 flex flex-col {{ ($plan['featured'] ?? false) ? 'ring-2 ring-indigo-500' : '' }}">
                        @if ($plan['featured'] ?? false)
                            <div class="text-xs font-semibold text-indigo-600 mb-2">NAJPOPULARNIEJSZY</div>
                        @endif
                        <div class="text-xl font-semibold">{{ $plan['name'] }}</div>
                        <div class="text-3xl font-bold mt-2">{{ $plan['price'] }}</div>
                        <ul class="mt-4 space-y-1.5 text-sm text-gray-700 flex-1">
                            @foreach ($plan['features'] as $f)
                                <li>✓ {{ $f }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('billing.checkout', $key) }}"
                           class="mt-6 inline-flex justify-center items-center px-4 py-2 rounded font-medium
                                  {{ ($plan['featured'] ?? false) ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                            @if (! $plan['price_id'])
                                Skontaktuj się z nami
                            @else
                                Wybierz {{ $plan['name'] }}
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>

            @if ($organization->hasStripeId())
                <div class="mt-6 text-center text-sm text-gray-600">
                    <a class="text-indigo-600" href="{{ route('billing.portal') }}">Zarządzaj subskrypcją w portalu Stripe →</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
