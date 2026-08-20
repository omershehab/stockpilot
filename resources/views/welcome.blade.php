@extends('layouts.marketing')

@php($landing = trans('marketing.landing'))

@section('title', $landing['meta_title'])
@section('meta_description', $landing['meta_description'])

@section('head')
<script type="application/ld+json">{!! json_encode([
    chr(64).'context' => 'https://schema.org',
    chr(64).'type' => 'SoftwareApplication',
    'name' => 'StockPilot',
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'description' => $landing['schema_description'],
    'inLanguage' => app()->getLocale(),
    'offers' => [chr(64).'type' => 'Offer', 'name' => $landing['starter'], 'price' => '0', 'priceCurrency' => 'SAR'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
<section class="overflow-hidden bg-slate-950 text-white">
    <div class="mx-auto grid max-w-7xl items-center gap-14 px-6 py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
        <div>
            <span class="badge bg-emerald-400/15 text-emerald-300">{{ $landing['hero_badge'] }}</span>
            <h1 class="mt-6 text-5xl font-black leading-[1.05] sm:text-6xl">{{ $landing['hero_title'] }} <span class="text-emerald-400">{{ $landing['hero_accent'] }}</span></h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">{{ $landing['hero_body'] }}</p>
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a class="btn-primary !px-6 !py-3" href="{{ route('register') }}">{{ $landing['hero_cta'] }}</a>
                <a class="text-sm font-semibold text-white hover:text-emerald-300" href="{{ route('marketing.features') }}">{{ $landing['hero_link'] }} <span class="rtl-flip inline-block">→</span></a>
            </div>
            <div class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-400"><span>✓ {{ $landing['no_card'] }}</span><span>✓ {{ $landing['no_trial'] }}</span><span>✓ {{ $landing['languages'] }}</span></div>
        </div>

        <div class="relative">
            <div class="absolute -inset-8 rounded-full bg-emerald-400/15 blur-3xl"></div>
            <div class="relative rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl backdrop-blur">
                <div class="flex items-center justify-between border-b border-white/10 pb-4"><div><small class="text-slate-400">{{ $landing['preview']['overview'] }}</small><strong class="mt-1 block">{{ $landing['preview']['today'] }}</strong></div><span class="badge bg-emerald-400 text-slate-950">{{ $landing['preview']['live'] }}</span></div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2"><div class="rounded-2xl bg-white p-5 text-slate-900"><small class="text-slate-500">{{ $landing['preview']['value'] }}</small><strong class="mt-2 flex items-center gap-2 text-3xl">128,450 <x-currency-symbol class="h-7" /></strong><span class="mt-4 inline-block text-sm font-semibold text-emerald-600">{{ $landing['preview']['accurate'] }}</span></div><div class="rounded-2xl bg-emerald-400 p-5 text-slate-950"><small>{{ $landing['preview']['counted'] }}</small><strong class="mt-2 block text-3xl">847 / 920</strong><div class="mt-5 h-2 rounded-full bg-emerald-900/20"><div class="h-2 w-11/12 rounded-full bg-slate-950"></div></div></div></div>
                <div class="mt-4 rounded-2xl bg-slate-900 p-5"><div class="mb-3 flex justify-between"><strong>{{ $landing['preview']['recent'] }}</strong><span class="text-xs text-emerald-400">● {{ $landing['preview']['updated'] }}</span></div>@foreach ($landing['preview']['rows'] as [$item,$type,$qty])<div class="grid grid-cols-[1fr_auto_auto] items-center gap-3 border-t border-slate-800 py-3 text-sm"><span>{{ $item }}</span><small class="text-slate-500">{{ $type }}</small><strong class="{{ str_starts_with($qty, '+') ? 'text-emerald-400' : 'text-rose-400' }}" dir="ltr">{{ $qty }}</strong></div>@endforeach</div>
            </div>
        </div>
    </div>
</section>

<section class="border-b border-slate-200 bg-emerald-50">
    <div class="mx-auto grid max-w-7xl gap-6 px-6 py-8 text-center sm:grid-cols-3 lg:px-8">@foreach($landing['stats'] as [$value,$label])<div><strong class="block text-2xl">{{ $value }}</strong><span class="text-sm text-slate-600">{{ $label }}</span></div>@endforeach</div>
</section>

<section class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
    <div class="max-w-3xl"><span class="text-sm font-bold uppercase tracking-widest text-emerald-700">{{ $landing['features_eyebrow'] }}</span><h2 class="mt-3 text-3xl font-black sm:text-4xl">{{ $landing['features_title'] }}</h2><p class="mt-4 text-lg leading-8 text-slate-600">{{ $landing['features_body'] }}</p></div>
    <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($landing['features'] as [$icon,$title,$body])
            <article class="rounded-2xl border border-slate-200 p-6 transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"><span class="grid h-12 w-12 place-items-center rounded-xl bg-emerald-100 text-xl text-emerald-800">{{ $icon }}</span><h3 class="mt-5 text-lg font-bold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p></article>
        @endforeach
    </div>
</section>

<section class="bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="text-center"><span class="text-sm font-bold uppercase tracking-widest text-emerald-700">{{ $landing['steps_eyebrow'] }}</span><h2 class="mt-3 text-3xl font-black">{{ $landing['steps_title'] }}</h2></div>
        <div class="mt-12 grid gap-8 md:grid-cols-3">@foreach ($landing['steps'] as [$number,$title,$body])<article class="relative rounded-2xl bg-white p-7 shadow-sm"><span class="grid h-10 w-10 place-items-center rounded-full bg-slate-950 font-black text-emerald-400">{{ $number }}</span><h3 class="mt-5 text-lg font-bold">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p></article>@endforeach</div>
    </div>
</section>

<section class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:px-8">
    <div><span class="text-sm font-bold uppercase tracking-widest text-emerald-700">{{ $landing['uses_eyebrow'] }}</span><h2 class="mt-3 text-3xl font-black">{{ $landing['uses_title'] }}</h2><p class="mt-5 leading-7 text-slate-600">{{ $landing['uses_body'] }}</p><div class="mt-7 grid gap-3 sm:grid-cols-2">@foreach ($landing['uses'] as $use)<div class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold">✓ {{ $use }}</div>@endforeach</div></div>
    <div class="rounded-3xl bg-slate-950 p-8 text-white"><span class="badge bg-emerald-400 text-slate-950">{{ $landing['starter'] }}</span><div class="mt-5 flex items-end gap-2"><strong class="text-5xl font-black">{{ $landing['free'] }}</strong><span class="pb-1 text-slate-400">{{ $landing['forever'] }}</span></div><p class="mt-4 text-slate-300">{{ $landing['plan_body'] }}</p><ul class="mt-7 space-y-3 text-sm text-slate-200">@foreach($landing['plan_items'] as $item)<li>✓ {{ $item }}</li>@endforeach</ul><a class="btn-primary mt-8 w-full" href="{{ route('register') }}">{{ __('marketing.shared.start_free_forever') }}</a><a class="mt-4 block text-center text-sm text-slate-400 hover:text-white" href="{{ route('marketing.pricing') }}">{{ $landing['pricing_link'] }}</a></div>
</section>

<section class="bg-emerald-400 text-slate-950">
    <div class="mx-auto flex max-w-5xl flex-col items-center px-6 py-16 text-center"><h2 class="text-3xl font-black sm:text-4xl">{{ $landing['final_title'] }}</h2><p class="mt-4 max-w-2xl text-lg">{{ $landing['final_body'] }}</p><a class="mt-7 inline-flex rounded-xl bg-slate-950 px-6 py-3 font-bold text-white hover:bg-slate-800" href="{{ route('register') }}">{{ $landing['final_cta'] }}</a></div>
</section>
@endsection
