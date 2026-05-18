<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Moje trasy</h2></x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm rounded-lg p-5">
                <div class="font-medium mb-2">Kalendarz iCal (Google / Apple / Outlook)</div>
                <p class="text-sm text-gray-600 mb-2">Subskrybuj poniższy adres w kalendarzu — Twoje trasy będą się aktualizować automatycznie:</p>
                <pre class="text-xs bg-gray-900 text-gray-100 p-2 rounded select-all overflow-x-auto">{{ route('calendar.feed', $user->calendar_token) }}</pre>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Nadchodzące trasy ({{ $upcoming->count() }})</div>
                @if ($upcoming->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">Brak nadchodzących tras.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr><th class="px-4 py-2">Data</th><th>Numer</th><th>Klient</th><th>Trasa</th><th>Koni</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($upcoming as $q)
                                <tr>
                                    <td class="px-4 py-2 font-medium">{{ $q->transport_date->format('Y-m-d') }}</td>
                                    <td><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td>{{ $q->client_name }}</td>
                                    <td class="text-gray-600">{{ \Illuminate\Support\Str::limit($q->from_address, 18) }} → {{ \Illuminate\Support\Str::limit($q->to_address, 18) }}</td>
                                    <td>{{ $q->horses_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Historia (20 ostatnich)</div>
                @if ($past->isEmpty())
                    <div class="px-5 py-8 text-center text-gray-500">Brak.</div>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            @foreach ($past as $q)
                                <tr>
                                    <td class="px-4 py-2 text-gray-500">{{ $q->transport_date->format('Y-m-d') }}</td>
                                    <td><a class="text-indigo-600" href="{{ route('quotes.show', $q) }}">{{ $q->number }}</a></td>
                                    <td>{{ $q->client_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
