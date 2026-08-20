@extends('layouts.marketing')

@section('title', $page['meta_title'])
@section('meta_description', $page['meta_description'])

@section('head')
<script type="application/ld+json">{!! json_encode([
    chr(64).'context' => 'https://schema.org',
    chr(64).'graph' => [
        [
            chr(64).'type' => 'BreadcrumbList',
            'itemListElement' => [
                [chr(64).'type' => 'ListItem', 'position' => 1, 'name' => __('marketing.shared.home'), 'item' => route('home')],
                [chr(64).'type' => 'ListItem', 'position' => 2, 'name' => $page['title'], 'item' => url()->current()],
            ],
        ],
        [
            chr(64).'type' => 'FAQPage',
            'mainEntity' => collect($page['faq'])->map(fn ($item) => [
                chr(64).'type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [chr(64).'type' => 'Answer', 'text' => $item['answer']],
            ])->all(),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
<section class="overflow-hidden bg-slate-950 text-white">
    <div class="mx-auto max-w-5xl px-6 py-20 text-center lg:px-8 lg:py-24">
        <a class="text-sm font-semibold text-emerald-400 hover:text-emerald-300" href="{{ route('home') }}">StockPilot /</a>
        <span class="ms-1 text-sm text-slate-400">{{ $page['eyebrow'] }}</span>
        <h1 class="mx-auto mt-5 max-w-4xl text-4xl font-black leading-tight sm:text-6xl">{{ $page['title'] }}</h1>
        <p class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-slate-300">{{ $page['description'] }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4"><a class="btn-primary !px-6 !py-3" href="{{ route('register') }}">{{ __('marketing.shared.start_free_forever') }}</a><a class="btn-secondary !border-slate-700 !bg-slate-900 !text-white" href="{{ route('marketing.pricing') }}">{{ __('marketing.shared.see_free_pricing') }}</a></div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($page['highlights'] as $index => $highlight)
            <article class="rounded-2xl border border-slate-200 p-6 transition hover:border-emerald-300 hover:shadow-lg"><span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 font-black text-emerald-800">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span><h2 class="mt-5 text-lg font-bold">{{ $highlight[0] }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $highlight[1] }}</p></article>
        @endforeach
    </div>
</section>

<section class="bg-slate-50">
    <div class="mx-auto max-w-6xl space-y-8 px-6 py-20 lg:px-8">
        @foreach ($page['sections'] as $section)
            <article class="grid gap-8 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm md:grid-cols-[1.2fr_1fr] md:p-10"><div><h2 class="text-2xl font-black">{{ $section['title'] }}</h2><p class="mt-4 leading-7 text-slate-600">{{ $section['body'] }}</p></div><ul class="space-y-3">@foreach($section['bullets'] as $bullet)<li class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">✓ {{ $bullet }}</li>@endforeach</ul></article>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-4xl px-6 py-20 lg:px-8">
    <div class="text-center"><span class="text-sm font-bold uppercase tracking-widest text-emerald-700">{{ __('marketing.shared.faq_eyebrow') }}</span><h2 class="mt-3 text-3xl font-black">{{ __('marketing.shared.questions_about', ['topic' => app()->isLocale('ar') ? $page['eyebrow'] : strtolower($page['eyebrow'])]) }}</h2></div>
    <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white px-6">@foreach($page['faq'] as $item)<details class="group py-5" @if($loop->first) open @endif><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold">{{ $item['question'] }}<span class="text-emerald-700 group-open:rotate-45">+</span></summary><p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $item['answer'] }}</p></details>@endforeach</div>
</section>

<section class="border-y border-slate-200 bg-emerald-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8"><h2 class="text-2xl font-black">{{ __('marketing.shared.continue_exploring') }}</h2><div class="mt-7 grid gap-5 md:grid-cols-3">@foreach($page['related_pages'] as $related)<a class="rounded-2xl border border-emerald-200 bg-white p-6 transition hover:-translate-y-1 hover:shadow-lg" href="{{ $related['route'] }}"><h3 class="font-bold">{{ $related['title'] }}</h3><p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $related['description'] }}</p><span class="mt-4 inline-block text-sm font-bold text-emerald-700">{{ __('marketing.shared.read_more') }} <span class="rtl-flip inline-block">→</span></span></a>@endforeach</div></div>
</section>

<section class="bg-slate-950 text-white"><div class="mx-auto max-w-4xl px-6 py-16 text-center"><h2 class="text-3xl font-black">{{ __('marketing.shared.ready_title') }}</h2><p class="mt-4 text-slate-300">{{ __('marketing.shared.ready_body') }}</p><a class="btn-primary mt-7 !px-6 !py-3" href="{{ route('register') }}">{{ __('marketing.shared.create_workspace') }}</a></div></section>
@endsection
