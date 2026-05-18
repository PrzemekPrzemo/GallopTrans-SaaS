<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Zespół</h2></x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success')) <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div> @endif
            @if (session('error'))   <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div> @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b font-medium">Członkowie ({{ $members->count() }})</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr><th class="px-4 py-2">Imię</th><th>Email</th><th>Rola</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($members as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $m->email }}</td>
                                <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $m->role }}</span></td>
                                <td class="px-4 py-2 text-right">
                                    @if ($m->role !== 'owner' && $m->id !== auth()->id() && auth()->user()->canManage())
                                        <form method="POST" action="{{ route('team.remove', $m) }}" onsubmit="return confirm('Usunąć użytkownika?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 text-xs">usuń</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (auth()->user()->canManage())
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <div class="font-medium mb-3">Zaproś nowego użytkownika</div>
                    <form method="POST" action="{{ route('team.invite') }}" class="flex gap-2">
                        @csrf
                        <input type="email" name="email" required placeholder="email@firma.pl" class="flex-1 rounded border-gray-300">
                        <select name="role" class="rounded border-gray-300">
                            <option value="operator">operator</option>
                            <option value="admin">admin</option>
                            <option value="driver">kierowca</option>
                        </select>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Wyślij zaproszenie</button>
                    </form>
                </div>
            @endif

            @if ($pending->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-5 py-3 border-b font-medium">Oczekujące zaproszenia ({{ $pending->count() }})</div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y">
                            @foreach ($pending as $p)
                                <tr>
                                    <td class="px-4 py-2">{{ $p->email }}</td>
                                    <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $p->role }}</span></td>
                                    <td class="px-4 py-2 text-gray-500 text-xs">wygasa {{ $p->expires_at->diffForHumans() }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button onclick="navigator.clipboard.writeText('{{ route('invitations.accept', $p->token) }}'); this.textContent='✓ skopiowano'" class="text-xs text-indigo-600">kopiuj link</button>
                                        ·
                                        <form method="POST" action="{{ route('team.revoke', $p) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 text-xs">cofnij</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
