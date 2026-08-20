<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <x-theme-init />
        <title>@yield('title', __('Platform admin')) · {{ config('app.name', 'StockPilot') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false"></div>
            <aside :class="sidebarOpen ? 'translate-x-0' : '{{ app()->isLocale('ar') ? 'translate-x-full' : '-translate-x-full' }}'" class="fixed inset-y-0 start-0 z-50 flex w-72 flex-col border-e border-slate-800 bg-[#0b1120] text-white transition-transform lg:translate-x-0">
                <div class="flex h-20 items-center justify-between border-b border-slate-800 px-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-black text-slate-950">SP</span><span><strong class="block">StockPilot</strong><small class="text-xs text-cyan-300">{{ __('Platform control center') }}</small></span></a>
                    <button @click="sidebarOpen = false" class="text-slate-400 lg:hidden" aria-label="{{ __('Close') }}">✕</button>
                </div>
                <div class="px-4 pt-5"><p class="px-3 text-[11px] font-bold uppercase tracking-[.18em] text-slate-500">{{ __('Monitoring') }}</p></div>
                @php $adminLinks = [
                    ['admin.dashboard', '⌂', 'Overview'],
                    ['admin.subscribers.index', '▣', 'Subscribers'],
                    ['admin.products.index', '▦', 'Products'],
                    ['admin.users.index', '♟', 'Users'],
                    ['admin.tickets.index', '✉', 'Support tickets'],
                ]; @endphp
                <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                    @foreach($adminLinks as [$routeName, $icon, $label])
                        <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition {{ request()->routeIs($routeName) || ($routeName === 'admin.subscribers.index' && request()->routeIs('admin.subscribers.*')) || ($routeName === 'admin.tickets.index' && request()->routeIs('admin.tickets.*')) ? 'bg-cyan-400 font-bold text-slate-950' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"><span class="w-6 text-center text-lg">{{ $icon }}</span><span>{{ __($label) }}</span></a>
                    @endforeach
                </nav>
                <div class="border-t border-slate-800 p-4">
                    @if($workspace = Auth::user()->activeCompanies->first())<a href="{{ route('companies.dashboard', $workspace) }}" class="mb-3 flex items-center gap-2 rounded-xl border border-slate-700 px-3 py-2.5 text-xs text-slate-300 hover:bg-slate-800"><span class="rtl-flip">←</span> {{ __('Return to company app') }}</a>@endif
                    <div class="flex items-center gap-3 px-2"><span class="grid h-9 w-9 place-items-center rounded-full bg-cyan-400 font-bold text-slate-950">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ Auth::user()->name }}</strong><small class="block truncate text-slate-400">{{ __('Platform admin') }}</small></span></div>
                    <div class="mt-3 flex gap-2"><a href="{{ route('profile.edit') }}" class="flex-1 rounded-lg bg-slate-800 px-3 py-2 text-center text-xs hover:bg-slate-700">{{ __('Profile') }}</a><form method="POST" action="{{ route('logout') }}" class="flex-1">@csrf<button class="w-full rounded-lg bg-slate-800 px-3 py-2 text-xs text-rose-300 hover:bg-slate-700">{{ __('Log out') }}</button></form></div>
                </div>
            </aside>

            <div class="lg:ps-72">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
                        <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="{{ __('Open menu') }}">☰</button>
                        <div class="min-w-0 flex-1"><h1 class="truncate text-xl font-bold">@yield('heading')</h1><p class="mt-0.5 truncate text-xs text-slate-500">@yield('subheading')</p></div>
                        <x-notification-bell />
                        <x-theme-toggle />
                        <x-language-switcher compact />
                        <div class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:flex"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> {{ __('Live platform data') }}</div>
                    </div>
                </header>
                <main class="p-4 sm:p-6 lg:p-8">
                    @if (session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
                    @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
