<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <x-theme-init />

        <title>{{ config('app.name', 'StockPilot') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 text-slate-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center bg-[radial-gradient(circle_at_top_right,_#064e3b,_#020617_55%)] px-4 py-10">
            <div class="absolute end-5 top-5 flex items-center gap-2"><x-theme-toggle /><x-language-switcher dark /></div>
            <a href="/" class="mb-6 flex items-center gap-3 text-white"><span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400 font-black text-slate-950">SP</span><span><strong class="block text-xl">StockPilot</strong><small class="text-slate-400">{{ __('Barcode & Inventory SaaS') }}</small></span></a>
            <div class="w-full max-w-md rounded-2xl bg-white px-7 py-7 shadow-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
