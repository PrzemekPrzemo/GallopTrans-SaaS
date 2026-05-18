<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Zapytania ofertowe</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2">Data</th>
                            <th class="px-4 py-2">Klient</th>
                            <th class="px-4 py-2">Trasa</th>
                            <th class="px-4 py-2">Konie</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($inquiries as $i)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-500">{{ $i->created_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ $i->client_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $i->client_email }} {{ $i->client_phone ? '· ' . $i->client_phone : '' }}</div>
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ \Illuminate\Support\Str::limit($i->from_address, 22) }} → {{ \Illuminate\Support\Str::limit($i->to_address, 22) }}
                                </td>
                                <td class="px-4 py-2">{{ $i->horses_count }}</td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('inquiries.status', $i) }}">
                                        @csrf @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="text-xs rounded border-gray-300">
                                            @foreach (['new', 'in_progress', 'quoted', 'closed', 'spam'] as $st)
                                                <option value="{{ $st }}" {{ $i->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('calculator.index', [
                                            'client_name'=>$i->client_name, 'client_email'=>$i->client_email,
                                            'client_phone'=>$i->client_phone, 'from_address'=>$i->from_address,
                                            'to_address'=>$i->to_address, 'transport_date'=>$i->transport_date?->format('Y-m-d'),
                                            'horses_count'=>$i->horses_count, 'inquiry_id'=>$i->id,
                                       ]) }}" class="text-indigo-600 text-sm">→ Wyceń</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Brak zapytań.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-2">{{ $inquiries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
