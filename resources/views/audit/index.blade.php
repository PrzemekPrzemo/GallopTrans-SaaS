<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Audit log</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap gap-2 items-end text-sm">
                <label>Akcja
                    <input name="action" value="{{ request('action') }}" placeholder="np. created, updated" class="mt-1 rounded border-gray-300">
                </label>
                <label>Encja
                    <select name="entity" class="mt-1 rounded border-gray-300">
                        <option value="">— wszystkie —</option>
                        @foreach ($entities as $e)
                            <option value="{{ $e }}" {{ request('entity') === $e ? 'selected' : '' }}>{{ $e }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Użytkownik
                    <select name="user_id" class="mt-1 rounded border-gray-300">
                        <option value="">— wszyscy —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ (int) request('user_id') === $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </label>
                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded">Filtruj</button>
                <a href="{{ route('audit.index') }}" class="text-gray-600">wyczyść</a>
            </form>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-2">Czas</th>
                            <th>Użytkownik</th>
                            <th>Akcja</th>
                            <th>Encja</th>
                            <th>ID</th>
                            <th>Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($logs as $l)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">{{ $l->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $users->firstWhere('id', $l->user_id)?->name ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $l->action }}</td>
                                <td class="px-4 py-2">{{ $l->entity }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $l->entity_id }}</td>
                                <td class="px-4 py-2">
                                    @if ($l->payload)
                                        <details>
                                            <summary class="cursor-pointer text-gray-500 text-xs">{{ count($l->payload) }} pól</summary>
                                            <pre class="text-xs bg-gray-50 p-2 rounded mt-1 overflow-x-auto">{{ json_encode($l->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Brak zdarzeń.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-2 border-t">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
