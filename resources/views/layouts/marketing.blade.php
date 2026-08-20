<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <x-theme-init />
    <title>@yield('title', __('marketing.meta.default_title'))</title>
    <meta name="description" content="@yield('meta_description', __('marketing.meta.default_description'))">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="StockPilot">
    <meta property="og:title" content="@yield('title', __('marketing.meta.default_title'))">
    <meta property="og:description" content="@yield('meta_description', __('marketing.meta.default_description'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:locale" content="{{ app()->isLocale('ar') ? 'ar_SA' : 'en_US' }}">
    <meta name="twitter:card" content="summary_large_image">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
</head>
<body class="bg-white text-slate-900 antialiased">
    <div class="bg-emerald-400 px-4 py-2 text-center text-sm font-bold text-slate-950">
        {{ __('marketing.layout.announcement') }}
        <a href="{{ route('marketing.pricing') }}" class="ms-2 underline decoration-2 underline-offset-2">{{ __('marketing.layout.see_pricing') }}</a>
    </div>

    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-3 py-4 sm:gap-5 sm:px-5 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 font-black">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-950 text-emerald-400">SP</span>
                <span class="hidden sm:inline">StockPilot</span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex" aria-label="{{ __('marketing.layout.main_navigation') }}">
                <a class="hover:text-emerald-700" href="{{ route('marketing.features') }}">{{ __('marketing.layout.features') }}</a>
                <a class="hover:text-emerald-700" href="{{ route('marketing.pricing') }}">{{ __('marketing.layout.pricing') }}</a>
                <a class="hover:text-emerald-700" href="{{ route('marketing.barcode-inventory-system') }}">{{ __('marketing.layout.barcode_inventory') }}</a>
                <a class="hover:text-emerald-700" href="{{ route('marketing.barcode-label-printing') }}">{{ __('marketing.layout.label_printing') }}</a>
                <a class="hover:text-emerald-700" href="{{ route('marketing.inventory-management-small-business') }}">{{ __('marketing.layout.small_business') }}</a>
            </nav>
            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <x-theme-toggle compact />
                <x-language-switcher compact />
                @auth
                    <a class="btn-primary" href="{{ route('dashboard') }}">{{ __('marketing.layout.dashboard') }}</a>
                @else
                    <a class="hidden text-sm font-semibold text-slate-600 hover:text-emerald-700 sm:inline" href="{{ route('login') }}">{{ __('marketing.layout.log_in') }}</a>
                    <a class="btn-primary" href="{{ route('register') }}">{{ __('marketing.layout.start_free') }}</a>
                @endauth
            </div>
        </div>
        <nav class="scrollbar-none flex gap-5 overflow-x-auto border-t border-slate-100 px-5 py-3 text-xs font-semibold text-slate-600 lg:hidden" aria-label="{{ __('marketing.layout.mobile_navigation') }}">
            <a class="whitespace-nowrap" href="{{ route('marketing.features') }}">{{ __('marketing.layout.features') }}</a>
            <a class="whitespace-nowrap" href="{{ route('marketing.pricing') }}">{{ __('marketing.layout.pricing') }}</a>
            <a class="whitespace-nowrap" href="{{ route('marketing.barcode-inventory-system') }}">{{ __('marketing.layout.barcode_inventory') }}</a>
            <a class="whitespace-nowrap" href="{{ route('marketing.barcode-label-printing') }}">{{ __('marketing.layout.label_printing') }}</a>
            <a class="whitespace-nowrap" href="{{ route('marketing.inventory-management-small-business') }}">{{ __('marketing.layout.small_business') }}</a>
        </nav>
    </header>

    <main>@yield('content')</main>

    <footer class="border-t border-slate-800 bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-black text-white"><span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-400 text-slate-950">SP</span>StockPilot</a>
                <p class="mt-4 text-sm leading-6 text-slate-400">{{ __('marketing.layout.summary') }}</p>
            </div>
            <div><h2 class="font-bold text-white">{{ __('marketing.layout.product') }}</h2><div class="mt-4 space-y-3 text-sm"><a class="block hover:text-emerald-400" href="{{ route('marketing.features') }}">{{ __('marketing.layout.features') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.pricing') }}">{{ __('marketing.layout.free_pricing') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.barcode-inventory-system') }}">{{ __('marketing.layout.barcode_inventory_system') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.barcode-label-printing') }}">{{ __('marketing.layout.barcode_label_printing') }}</a></div></div>
            <div><h2 class="font-bold text-white">{{ __('marketing.layout.solutions') }}</h2><div class="mt-4 space-y-3 text-sm"><a class="block hover:text-emerald-400" href="{{ route('marketing.inventory-management-small-business') }}">{{ __('marketing.layout.small_business_inventory') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.warehouse-inventory-management') }}">{{ __('marketing.layout.warehouse_management') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.stock-counting-software') }}">{{ __('marketing.layout.stock_counting') }}</a><a class="block hover:text-emerald-400" href="{{ route('marketing.inventory-management-saudi-arabia') }}">{{ __('marketing.layout.saudi_inventory') }}</a></div></div>
            <div><h2 class="font-bold text-white">{{ __('marketing.layout.get_started') }}</h2><p class="mt-4 text-sm leading-6 text-slate-400">{{ __('marketing.layout.get_started_body') }}</p><a class="btn-primary mt-5" href="{{ route('register') }}">{{ __('marketing.layout.create_workspace') }}</a></div>
        </div>
        <div class="border-t border-slate-800 px-6 py-5 text-center text-xs text-slate-500">© {{ now()->year }} StockPilot · {{ __('marketing.layout.copyright') }}</div>
    </footer>
</body>
</html>
