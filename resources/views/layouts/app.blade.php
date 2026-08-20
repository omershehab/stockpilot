<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <x-theme-init />

        <title>{{ isset($currentCompany) ? $currentCompany->name.' · ' : '' }}{{ config('app.name', 'StockPilot') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            @include('layouts.navigation')
            <div class="{{ isset($currentCompany) ? 'lg:ps-72' : '' }}">
                @isset($header)
                    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
                        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-5 sm:px-6 lg:px-8">
                            @isset($currentCompany)<button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="{{ __('Open menu') }}">☰</button>@endisset
                            <div class="min-w-0 flex-1">{{ $header }}</div>
                            <x-notification-bell />
                            <x-theme-toggle />
                            <x-language-switcher compact />
                        </div>
                    </header>
                @endisset
                <main class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
                    @if (session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
                    @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
