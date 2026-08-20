@if (isset($currentCompany))
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>
    <aside :class="sidebarOpen ? 'translate-x-0' : '{{ app()->isLocale('ar') ? 'translate-x-full' : '-translate-x-full' }}'" class="fixed inset-y-0 start-0 z-50 flex w-72 flex-col border-e border-slate-800 bg-slate-950 text-white transition-transform lg:translate-x-0">
        @php
            $homeUrl = in_array('dashboard.view', $currentPermissions ?? [], true)
                ? route('companies.dashboard', $currentCompany)
                : (in_array('people.manage', $currentPermissions ?? [], true) ? route('companies.people.index', $currentCompany) : route('profile.edit'));
        @endphp
        <div class="flex h-20 items-center justify-between border-b border-slate-800 px-5">
            <a href="{{ $homeUrl }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500 text-sm font-black text-slate-950">SP</span><span><strong class="block text-base">StockPilot</strong><small class="block text-xs text-slate-400">{{ __('Inventory SaaS') }}</small></span></a>
            <button @click="sidebarOpen = false" class="text-slate-400 lg:hidden" aria-label="{{ __('Close') }}">✕</button>
        </div>
        <div class="border-b border-slate-800 p-4"><div class="rounded-xl bg-slate-900 p-3"><p class="truncate text-sm font-semibold">{{ $currentCompany->name }}</p><p class="mt-1 text-xs text-slate-400">{{ __(ucfirst($currentMembership->role)) }} · {{ __(ucfirst($currentCompany->plan)) }}</p></div></div>
        @php $links = [
            ['companies.dashboard', '◫', 'Dashboard', 'dashboard.view'],
            ['companies.products.index', '▦', 'Products', 'products.view'],
            ['companies.movements.index', '⇄', 'Stock movements', 'inventory.view'],
            ['companies.stock-counts.index', '✓', 'Stock counts', 'inventory.view'],
            ['companies.warehouses.index', '⌂', 'Warehouses', 'warehouses.manage'],
            ['companies.labels.index', '▥', 'Print labels', 'labels.print'],
            ['companies.label-templates.index', '▤', 'Label designer', 'labels.manage'],
            ['companies.people.index', '♟', 'Users & teams', 'people.manage'],
        ]; @endphp
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            @foreach ($links as [$routeName, $icon, $label, $permission])
                @continue(! in_array($permission, $currentPermissions ?? [], true))
                <a href="{{ route($routeName, $currentCompany) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition {{ request()->routeIs($routeName) || request()->routeIs(str_replace('.index', '.*', $routeName)) ? 'bg-emerald-500 font-semibold text-slate-950' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"><span class="w-6 text-center text-lg">{{ $icon }}</span><span>{{ __($label) }}</span></a>
            @endforeach
            <a href="{{ route('companies.tickets.index', $currentCompany) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition {{ request()->routeIs('companies.tickets.*') ? 'bg-emerald-500 font-semibold text-slate-950' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"><span class="w-6 text-center text-lg">?</span><span>{{ __('Support tickets') }}</span></a>
        </nav>
        <div class="border-t border-slate-800 p-4" x-data="{ open: false }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl p-2 text-start hover:bg-slate-900"><span class="grid h-9 w-9 place-items-center rounded-full bg-slate-700 text-sm font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ Auth::user()->name }}</strong><small class="block truncate text-slate-400" dir="ltr">{{ Auth::user()->email }}</small></span></button>
            <div x-show="open" x-cloak class="mt-2 space-y-1 rounded-xl bg-slate-900 p-2">
                @foreach (Auth::user()->activeCompanies as $companyOption)<a href="{{ route('companies.dashboard', $companyOption) }}" class="block truncate rounded-lg px-3 py-2 text-xs hover:bg-slate-800">{{ $companyOption->name }}</a>@endforeach
                <a href="{{ route('companies.create') }}" class="block rounded-lg px-3 py-2 text-xs text-emerald-400 hover:bg-slate-800">+ {{ __('New company') }}</a>
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-xs hover:bg-slate-800">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full rounded-lg px-3 py-2 text-start text-xs text-rose-300 hover:bg-slate-800">{{ __('Log out') }}</button></form>
            </div>
        </div>
    </aside>
@else
    <nav class="border-b border-slate-200 bg-white"><div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"><a href="/" class="font-black text-emerald-700">StockPilot</a><div class="flex items-center gap-3"><a href="{{ route('profile.edit') }}" class="text-sm text-slate-600">{{ Auth::user()->name }}</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-rose-600">{{ __('Log out') }}</button></form></div></div></nav>
@endif
