<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">👑 Organizacje</h2></x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-4 py-2">Firma</th>
                            <th>Slug</th>
                            <th class="text-right">Userów</th>
                            <th class="text-right">Wycen</th>
                            <th>Status</th>
                            <th>Utworzono</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($orgs as $org)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2"><a href="{{ route('admin.organization', $org) }}" class="text-indigo-600 font-medium">{{ $org->name }}</a></td>
                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $org->slug }}</td>
                                <td class="px-4 py-2 text-right">{{ $org->users_count }}</td>
                                <td class="px-4 py-2 text-right">{{ $org->quotes_count }}</td>
                                <td class="px-4 py-2">
                                    @if ($org->subscribed('default'))
                                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-800 rounded">płacący</span>
                                    @elseif ($org->trial_ends_at && $org->trial_ends_at->isFuture())
                                        <span class="text-xs px-2 py-0.5 bg-amber-100 text-amber-800 rounded">trial</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded">expired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $org->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-2 border-t">{{ $orgs->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
