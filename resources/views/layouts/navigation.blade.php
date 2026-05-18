<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Pulpit') }}
                    </x-nav-link>
                    <x-nav-link :href="route('calculator.index')" :active="request()->routeIs('calculator.*')">
                        {{ __('Kalkulator') }}
                    </x-nav-link>
                    <x-nav-link :href="route('quotes.index')" :active="request()->routeIs('quotes.*')">
                        {{ __('Oferty') }}
                    </x-nav-link>
                    <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                        {{ __('Faktury') }}
                    </x-nav-link>
                    <x-nav-link :href="route('inquiries.index')" :active="request()->routeIs('inquiries.*')">
                        {{ __('Zapytania') }}
                    </x-nav-link>
                    <x-nav-link :href="route('driver.dashboard')" :active="request()->routeIs('driver.*')">
                        {{ __('Moje trasy') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        {{ __('Raporty') }}
                    </x-nav-link>
                    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                        {{ __('Klienci') }}
                    </x-nav-link>
                    <x-nav-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*')">
                        {{ __('Pojazdy') }}
                    </x-nav-link>
                    <x-nav-link :href="route('team.index')" :active="request()->routeIs('team.*')">
                        {{ __('Zespół') }}
                    </x-nav-link>
                    <x-nav-link :href="route('settings.edit')" :active="request()->routeIs('settings.*')">
                        {{ __('Ustawienia') }}
                    </x-nav-link>
                    <x-nav-link :href="route('billing.plans')" :active="request()->routeIs('billing.*')">
                        {{ __('Subskrypcja') }}
                    </x-nav-link>
                    @if (auth()->user()?->is_super_admin)
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            👑 Admin
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Locale switcher + Bell + Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">

                @php
                    $localeFlags = ['pl' => '🇵🇱', 'en' => '🇬🇧', 'de' => '🇩🇪'];
                    $current = app()->getLocale();
                @endphp
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center px-2 py-1 text-sm rounded hover:bg-gray-100" title="{{ __('Język') }}">
                        <span class="text-base">{{ $localeFlags[$current] ?? '🌐' }}</span>
                        <span class="ml-1 uppercase text-xs text-gray-600">{{ $current }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-1 w-32 bg-white rounded shadow-lg border z-50 text-sm">
                        @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $loc)
                            <a href="{{ route('locale.switch', $loc) }}"
                               class="flex items-center px-3 py-2 hover:bg-gray-50 {{ $loc === $current ? 'bg-indigo-50 font-medium' : '' }}">
                                <span class="mr-2 text-base">{{ $localeFlags[$loc] }}</span>
                                <span class="uppercase">{{ $loc }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Bell — polluje endpoint co 60s żeby pokazać unread + lista 10 ostatnich --}}
                <div id="gt-bell" class="relative" style="display:none;">
                    <button id="gt-bell-btn" class="relative p-2 rounded-full hover:bg-gray-100" title="{{ __('Powiadomienia') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span id="gt-bell-count" class="hidden absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">0</span>
                    </button>
                    <div id="gt-bell-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white shadow-lg rounded border z-50">
                        <div class="px-4 py-2 border-b flex justify-between items-center text-sm">
                            <strong>{{ __('Powiadomienia') }}</strong>
                            <button id="gt-bell-mark-all" class="text-xs text-indigo-600 hover:underline">{{ __('Oznacz wszystkie jako przeczytane') }}</button>
                        </div>
                        <div id="gt-bell-list" class="max-h-96 overflow-y-auto text-sm">
                            <div class="px-4 py-6 text-center text-gray-500">Ładowanie…</div>
                        </div>
                    </div>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Wyloguj') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Wyloguj') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

@auth
@if (Auth::user()->organization_id)
<script>
(function () {
    const bell = document.getElementById('gt-bell');
    const btn  = document.getElementById('gt-bell-btn');
    const cnt  = document.getElementById('gt-bell-count');
    const dd   = document.getElementById('gt-bell-dropdown');
    const list = document.getElementById('gt-bell-list');
    const mark = document.getElementById('gt-bell-mark-all');
    if (!bell) return;
    bell.style.display = '';

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    async function refresh() {
        try {
            const r = await fetch('{{ route('notifications.index') }}', { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (data.unread > 0) { cnt.textContent = data.unread; cnt.classList.remove('hidden'); }
            else { cnt.classList.add('hidden'); }
            list.innerHTML = '';
            if (!data.items.length) {
                list.innerHTML = '<div class="px-4 py-6 text-center text-gray-500">Brak powiadomień.</div>';
                return;
            }
            for (const n of data.items) {
                const row = document.createElement('a');
                row.href = n.link || '#';
                row.className = 'block px-4 py-2 border-b hover:bg-gray-50 ' + (n.read ? 'opacity-60' : 'bg-indigo-50');
                row.innerHTML = '<div class="font-medium">' + (n.read ? '' : '• ') + escapeHtml(n.title) + '</div>' +
                                (n.message ? '<div class="text-xs text-gray-600">' + escapeHtml(n.message) + '</div>' : '') +
                                '<div class="text-xs text-gray-400">' + n.created_at + '</div>';
                row.onclick = () => fetch('{{ url('/notifications') }}/' + n.id + '/read', { method:'POST', headers: { 'X-CSRF-TOKEN': csrf } });
                list.appendChild(row);
            }
        } catch (e) { /* silent */ }
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    btn.onclick = (e) => { e.stopPropagation(); dd.classList.toggle('hidden'); if (!dd.classList.contains('hidden')) refresh(); };
    document.addEventListener('click', (e) => { if (!bell.contains(e.target)) dd.classList.add('hidden'); });
    mark.onclick = async (e) => {
        e.stopPropagation();
        await fetch('{{ url('/notifications') }}/0/read', { method:'POST', headers: { 'X-CSRF-TOKEN': csrf } });
        refresh();
    };

    refresh();
    setInterval(refresh, 60000);
})();
</script>
@endif
@endauth
