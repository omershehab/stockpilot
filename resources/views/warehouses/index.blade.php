<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold">{{ __('Warehouses') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Manage storage locations and track stock across your company.') }} @if($company->warehouse_limit !== null)<span class="ms-1">{{ __('Usage: :used of :limit', ['used' => $warehouses->count(), 'limit' => $company->warehouse_limit]) }}</span>@endif</p></div>
            @if($company->hasReachedWarehouseLimit($warehouses->count()))<span class="badge bg-amber-100 text-amber-800">{{ __('Warehouse limit reached') }}</span>@else<a href="{{ route('companies.warehouses.create', $company) }}" class="btn-primary">+ {{ __('Add warehouse') }}</a>@endif
        </div>
    </x-slot>

    @php
        $activeCount = $warehouses->where('is_active', true)->count();
        $totalUnits = $warehouses->sum(fn ($warehouse) => (float) ($warehouse->stocks_sum_quantity ?? 0));
        $stockRecords = $warehouses->sum('stocks_count');
    @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5"><p class="text-sm text-slate-500">{{ __('Total warehouses') }}</p><strong class="mt-2 block text-3xl">{{ $warehouses->count() }}</strong></div>
        <div class="card p-5"><p class="text-sm text-slate-500">{{ __('Active locations') }}</p><strong class="mt-2 block text-3xl text-emerald-600">{{ $activeCount }}</strong></div>
        <div class="card p-5"><p class="text-sm text-slate-500">{{ __('Units across warehouses') }}</p><strong class="mt-2 block text-3xl">{{ number_format($totalUnits, 3) }}</strong></div>
        <div class="card p-5"><p class="text-sm text-slate-500">{{ __('Stocked product records') }}</p><strong class="mt-2 block text-3xl">{{ number_format($stockRecords) }}</strong></div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        @forelse($warehouses as $warehouse)
            <article class="card overflow-hidden {{ $warehouse->is_default ? 'border-emerald-300' : '' }}">
                <div class="flex items-start gap-4 p-5">
                    <span class="grid h-12 w-12 flex-none place-items-center rounded-2xl {{ $warehouse->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }} text-xl">⌂</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="truncate font-bold">{{ $warehouse->name }}</h2>@if($warehouse->is_default)<span class="badge bg-emerald-100 text-emerald-700">{{ __('Default') }}</span>@endif<span class="badge {{ $warehouse->is_active ? 'bg-blue-100 text-blue-700' : 'bg-slate-200 text-slate-600' }}">{{ $warehouse->is_active ? __('Active') : __('Inactive') }}</span></div>
                        <p class="mt-1 text-sm text-slate-500">{{ $warehouse->address ?: __('No address provided') }}</p>
                    </div>
                    <a href="{{ route('companies.warehouses.edit', [$company, $warehouse]) }}" class="btn-secondary !px-3 !py-2">{{ __('Edit') }}</a>
                </div>
                <div class="grid grid-cols-3 border-t border-slate-100 bg-slate-50">
                    <div class="border-e border-slate-200 p-4"><small class="block text-slate-400">{{ __('Code') }}</small><strong class="mt-1 block font-mono text-sm">{{ $warehouse->code }}</strong></div>
                    <div class="border-e border-slate-200 p-4"><small class="block text-slate-400">{{ __('On hand') }}</small><strong class="mt-1 block text-sm">{{ number_format($warehouse->stocks_sum_quantity ?? 0, 3) }}</strong></div>
                    <div class="p-4"><small class="block text-slate-400">{{ __('Stocked items') }}</small><strong class="mt-1 block text-sm">{{ $warehouse->stocks_count }}</strong></div>
                </div>
            </article>
        @empty
            <div class="card p-12 text-center lg:col-span-2"><span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-100 text-3xl text-emerald-700">⌂</span><h2 class="mt-4 font-bold">{{ __('No warehouses found') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Add your first storage location to begin tracking stock.') }}</p>@if(! $company->hasReachedWarehouseLimit(0))<a href="{{ route('companies.warehouses.create', $company) }}" class="btn-primary mt-5">{{ __('Add warehouse') }}</a>@endif</div>
        @endforelse
    </div>

    <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900"><strong>{{ __('How default warehouses work') }}</strong><p class="mt-1 text-blue-700">{{ __('The default warehouse is used as the primary company location. Choose another warehouse as default before deactivating it.') }}</p></div>
</x-app-layout>
