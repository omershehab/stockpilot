@extends('layouts.admin')
@section('title', __('Overview'))
@section('heading', __('Platform overview'))
@section('subheading', __('Live health, adoption, and inventory activity across every subscriber'))

@section('content')
    <section class="relative mb-6 overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
        <div class="absolute -end-20 -top-24 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="absolute -bottom-24 start-1/3 h-56 w-56 rounded-full bg-violet-500/10 blur-3xl"></div>
        <div class="relative flex flex-col justify-between gap-7 xl:flex-row xl:items-end">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-cyan-200"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>{{ __('System operational') }}</span>
                <h2 class="mt-5 text-2xl font-black sm:text-3xl">{{ __('Welcome back, :name', ['name' => Auth::user()->name]) }}</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">{{ __('Review platform health, respond to customers, and spot companies approaching their usage limits.') }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-[.16em] text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3">
                <a href="{{ route('admin.tickets.index') }}" class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:border-cyan-400/50 hover:bg-white/10"><span class="grid h-9 w-9 place-items-center rounded-xl bg-cyan-400 text-slate-950">✉</span><span><small class="block text-slate-400">{{ __('Support queue') }}</small><strong class="text-sm">{{ __(':count open', ['count' => $metrics['open_tickets']]) }}</strong></span></a>
                <a href="{{ route('admin.subscribers.index') }}" class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:border-cyan-400/50 hover:bg-white/10"><span class="grid h-9 w-9 place-items-center rounded-xl bg-violet-400 text-slate-950">▣</span><span><small class="block text-slate-400">{{ __('Companies') }}</small><strong class="text-sm">{{ __('Manage subscribers') }}</strong></span></a>
                <a href="{{ route('admin.products.index') }}" class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:border-cyan-400/50 hover:bg-white/10"><span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-300 text-slate-950">▦</span><span><small class="block text-slate-400">{{ __('Catalog') }}</small><strong class="text-sm">{{ __('Review products') }}</strong></span></a>
            </div>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $metricCards = [
                ['route' => 'admin.subscribers.index', 'icon' => '▣', 'iconClass' => 'bg-cyan-100 text-cyan-700', 'label' => 'Subscribers', 'value' => number_format($metrics['companies']), 'badge' => $metrics['active_companies'].' '.__('Active'), 'badgeClass' => 'bg-emerald-100 text-emerald-700', 'detail' => __(':trials trials · :expiring expiring soon', ['trials' => $metrics['trial_companies'], 'expiring' => $metrics['expiring_trials']])],
                ['route' => 'admin.users.index', 'icon' => '♟', 'iconClass' => 'bg-violet-100 text-violet-700', 'label' => 'Platform users', 'value' => number_format($metrics['users']), 'badge' => __('All accounts'), 'badgeClass' => 'bg-violet-50 text-violet-700', 'detail' => __('Across :count workspaces', ['count' => $metrics['companies']])],
                ['route' => 'admin.products.index', 'icon' => '▦', 'iconClass' => 'bg-amber-100 text-amber-700', 'label' => 'Products', 'value' => number_format($metrics['products']), 'badge' => __('Catalog total'), 'badgeClass' => 'bg-amber-50 text-amber-700', 'detail' => __(':count units on hand', ['count' => number_format($metrics['inventory_units'], 3)])],
                ['route' => null, 'icon' => '⇄', 'iconClass' => 'bg-emerald-100 text-emerald-700', 'label' => 'Stock activity', 'value' => number_format($metrics['transactions_30d']), 'badge' => __('30 days'), 'badgeClass' => 'bg-blue-50 text-blue-700', 'detail' => __('Platform-wide transactions')],
            ];
        @endphp
        @foreach($metricCards as $metric)
            @if($metric['route'])<a href="{{ route($metric['route']) }}" @else<div @endif class="card group relative overflow-hidden p-5 transition hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-lg">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent opacity-0 transition group-hover:opacity-100"></div>
                <div class="flex items-start justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl text-xl {{ $metric['iconClass'] }}">{{ $metric['icon'] }}</span><span class="badge {{ $metric['badgeClass'] }}">{{ $metric['badge'] }}</span></div>
                <p class="mt-5 text-sm font-medium text-slate-500">{{ __($metric['label']) }}</p><strong class="mt-1 block text-3xl tracking-tight">{{ $metric['value'] }}</strong><p class="mt-2 truncate text-xs text-slate-400">{{ $metric['detail'] }}</p>
            @if($metric['route'])</a>@else</div>@endif
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        @php
            $attentionCards = [
                ['route' => 'admin.tickets.index', 'icon' => '✉', 'tone' => 'rose', 'iconClass' => 'bg-rose-100 text-rose-700', 'hoverClass' => 'hover:border-rose-300', 'label' => 'Open support tickets', 'value' => $metrics['open_tickets'], 'detail' => __(':count urgent', ['count' => $metrics['urgent_tickets']])],
                ['route' => 'admin.subscribers.index', 'icon' => '◔', 'tone' => 'amber', 'iconClass' => 'bg-amber-100 text-amber-700', 'hoverClass' => 'hover:border-amber-300', 'label' => 'Capacity alerts', 'value' => $quotaWatchlist->count(), 'detail' => __('Companies at 80% or more of a limit')],
                ['route' => 'admin.subscribers.index', 'icon' => '!', 'tone' => 'slate', 'iconClass' => 'bg-slate-100 text-slate-700', 'hoverClass' => 'hover:border-slate-400', 'label' => 'Account attention', 'value' => $metrics['disabled_companies'] + $metrics['subscription_issues'], 'detail' => __(':disabled disabled · :billing billing issues', ['disabled' => $metrics['disabled_companies'], 'billing' => $metrics['subscription_issues']])],
            ];
        @endphp
        @foreach($attentionCards as $item)
            <a href="{{ route($item['route']) }}" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition {{ $item['hoverClass'] }} hover:shadow-md">
                <span class="grid h-11 w-11 flex-none place-items-center rounded-xl text-lg font-black {{ $item['iconClass'] }}">{{ $item['icon'] }}</span>
                <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3"><strong class="text-sm">{{ __($item['label']) }}</strong><b class="text-xl">{{ $item['value'] }}</b></span><small class="mt-1 block truncate text-slate-400">{{ $item['detail'] }}</small></span><span class="rtl-flip text-slate-300 transition group-hover:translate-x-1 group-hover:text-slate-600">→</span>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(340px,.8fr)]">
        <section class="card p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3"><div><h2 class="font-bold">{{ __('Subscriber growth') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('New company workspaces by week') }}</p></div><span class="badge bg-slate-100 text-slate-600">{{ __('Last 8 weeks') }}</span></div>
            @php $maxWeekly = max(1, (int) $weeklySignups->max('total')); @endphp
            <div class="mt-8 flex h-52 items-end gap-3 border-b border-slate-200 px-2">
                @foreach($weeklySignups as $week)
                    <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"><span class="mb-2 text-xs font-bold text-slate-600">{{ $week['total'] }}</span><div class="w-full max-w-12 rounded-t-xl bg-gradient-to-t from-cyan-500 to-cyan-300 transition group-hover:from-cyan-600 group-hover:to-cyan-400" style="height: {{ max(4, ($week['total'] / $maxWeekly) * 82) }}%"></div><span class="mt-2 whitespace-nowrap text-[10px] text-slate-400">{{ $week['label'] }}</span></div>
                @endforeach
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4"><div class="flex items-center justify-between gap-3"><div><h2 class="font-bold">{{ __('Capacity watchlist') }}</h2><p class="mt-1 text-xs text-slate-500">{{ __('Companies closest to a usage limit') }}</p></div><span class="badge bg-amber-100 text-amber-700">{{ $quotaWatchlist->count() }}</span></div></div>
            <div class="divide-y divide-slate-100">
                @forelse($quotaWatchlist as $company)
                    <a href="{{ route('admin.subscribers.show', $company) }}" class="block p-4 transition hover:bg-slate-50">
                        <div class="flex items-center justify-between gap-3"><strong class="truncate text-sm">{{ $company->name }}</strong><span class="badge {{ $company->quota_percent >= 100 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">{{ $company->quota_percent }}%</span></div>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500"><span>{{ __($company->quota_resource) }}</span><span>{{ $company->quota_used }} / {{ $company->quota_limit }}</span></div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $company->quota_percent >= 100 ? 'bg-rose-500' : 'bg-amber-400' }}" style="width: {{ min(100, $company->quota_percent) }}%"></div></div>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-xl text-emerald-700">✓</span><strong class="mt-3 block text-sm">{{ __('Capacity looks healthy') }}</strong><p class="mt-1 text-xs text-slate-400">{{ __('No company is currently near a usage limit.') }}</p></div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(340px,.8fr)]">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 class="font-bold">{{ __('Recent subscribers') }}</h2><p class="text-xs text-slate-500">{{ __('Newest company workspaces') }}</p></div><a href="{{ route('admin.subscribers.index') }}" class="text-sm font-semibold text-cyan-700 hover:underline">{{ __('View all') }} <span class="rtl-flip inline-block">→</span></a></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[700px] text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Company') }}</th><th class="px-5 py-3 text-start">{{ __('Usage') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th><th class="px-5 py-3 text-start">{{ __('Joined') }}</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($recentCompanies as $company)<tr class="group hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('admin.subscribers.show', $company) }}" class="font-semibold group-hover:text-cyan-700">{{ $company->name }}</a><small class="block text-slate-400">{{ $company->users->first()?->email ?? $company->email }}</small></td><td class="px-5 py-4 text-slate-500">{{ __(':users users · :products products', ['users' => $company->users_count, 'products' => $company->products_count]) }}</td><td class="px-5 py-4"><span class="badge {{ $company->subscription_status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($company->subscription_status === 'trial' ? 'bg-cyan-100 text-cyan-700' : 'bg-amber-100 text-amber-700') }}">{{ __(str_replace('_', ' ', ucfirst($company->subscription_status))) }}</span></td><td class="px-5 py-4 text-slate-500">{{ $company->created_at->diffForHumans() }}</td><td class="px-5 py-4 text-end"><a href="{{ route('admin.subscribers.show', $company) }}" class="rtl-flip inline-block text-slate-300 group-hover:text-cyan-600">→</a></td></tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">{{ __('No subscribers yet.') }}</td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="card p-5 sm:p-6">
            <h2 class="font-bold">{{ __('Subscription health') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Current workspace distribution') }}</p>
            <div class="mt-6 space-y-5">
                @php $statusColors = ['active' => 'bg-emerald-500', 'trial' => 'bg-cyan-500', 'past_due' => 'bg-amber-500', 'cancelled' => 'bg-rose-500', 'canceled' => 'bg-rose-500']; @endphp
                @forelse($statusMix as $item)
                    <div><div class="flex items-center justify-between text-sm"><span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $statusColors[$item->subscription_status] ?? 'bg-slate-400' }}"></span>{{ __(str_replace('_', ' ', ucfirst($item->subscription_status))) }}</span><strong>{{ $item->total }}</strong></div><div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $statusColors[$item->subscription_status] ?? 'bg-slate-400' }}" style="width: {{ $totalCompanies ? ($item->total / $totalCompanies) * 100 : 0 }}%"></div></div></div>
                @empty<p class="py-8 text-center text-sm text-slate-400">{{ __('No subscription data yet.') }}</p>@endforelse
            </div>
            <div class="mt-7 border-t border-slate-100 pt-5"><p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('Plan mix') }}</p><div class="flex flex-wrap gap-2">@foreach($planMix as $item)<span class="badge bg-slate-100 text-slate-700">{{ __(ucfirst($item->plan)) }} <strong class="ms-1">{{ $item->total }}</strong></span>@endforeach</div></div>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden"><div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 class="font-bold">{{ __('Live activity') }}</h2><p class="text-xs text-slate-500">{{ __('Latest stock transactions across the platform') }}</p></div><span class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>{{ __('Live') }}</span></div><div class="grid divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0 rtl:md:divide-x-reverse">@forelse($recentActivity->chunk(4) as $chunk)<div class="divide-y divide-slate-100">@foreach($chunk as $activity)<a href="{{ $activity->company ? route('admin.subscribers.show', $activity->company) : '#' }}" class="flex items-center gap-3 p-4 transition hover:bg-slate-50"><span class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-blue-50 text-blue-700">⇄</span><span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ $activity->company?->name ?? __('Deleted company') }}</strong><small class="text-slate-500">{{ __(str_replace('_', ' ', ucfirst($activity->type))) }} · {{ __(':count lines', ['count' => $activity->lines_count]) }} · {{ $activity->user?->name ?? __('System') }}</small></span><time class="text-xs text-slate-400">{{ $activity->occurred_at->diffForHumans() }}</time></a>@endforeach</div>@empty<p class="col-span-2 p-10 text-center text-sm text-slate-400">{{ __('No activity yet.') }}</p>@endforelse</div></section>
@endsection
