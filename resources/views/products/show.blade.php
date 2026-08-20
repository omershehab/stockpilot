<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ app()->isLocale('en') ? $product->name_ar : $product->name }} · {{ $product->sku }}</p>
            </div>
            <div class="flex gap-2">
                @if(in_array('labels.print', $currentPermissions, true))
                <a class="btn-secondary" href="{{ route('companies.products.label', [$company, $product]) }}" target="_blank">{{ __('Print barcode') }}</a>
                @endif
                @if(in_array('products.manage', $currentPermissions, true))
                <a class="btn-primary" href="{{ route('companies.products.edit', [$company, $product]) }}">{{ __('Edit product') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card overflow-hidden">
            <div class="flex aspect-[4/3] items-center justify-center bg-slate-50">
                @if ($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-contain">
                @else
                    <div class="text-center text-sm text-slate-400">
                        <svg class="mx-auto mb-2 h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Z" /></svg>
                        {{ __('No product image') }}
                    </div>
                @endif
            </div>
        </section>

        <section class="card p-6 lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-sm text-slate-500">{{ __('Primary barcode') }}</p>
                    <strong class="mt-1 block break-all font-mono text-xl">{{ $product->primary_barcode?->code ?? '—' }}</strong>
                </div>
                <div><small class="text-slate-500">{{ __('Category') }}</small><strong class="block">{{ $product->category ? (app()->isLocale('ar') && $product->category->name_ar ? $product->category->name_ar : $product->category->name) : '—' }}</strong></div>
                <div><small class="text-slate-500">{{ __('Unit') }}</small><strong class="block">{{ $product->unit }}</strong></div>
                <div><small class="text-slate-500">{{ __('Cost') }}</small><strong class="block">{{ number_format($product->cost, 2) }} <x-currency-symbol :currency="$company->currency" /></strong></div>
                <div><small class="text-slate-500">{{ __('Price') }}</small><strong class="block">{{ number_format($product->price, 2) }} <x-currency-symbol :currency="$company->currency" /></strong></div>
            </div>
        </section>

        <section class="card overflow-hidden lg:col-span-3">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div><h2 class="font-bold">{{ __('Batches & expiry dates') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __(':count batch(es)', ['count' => $product->batches->count()]) }}</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Batch number') }}</th><th class="px-5 py-3 text-start">{{ __('Expiry date') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($product->batches as $batch)
                            <tr><td class="px-5 py-3 font-semibold">{{ $batch->batch_number }}</td><td class="px-5 py-3">{{ $batch->expiry_date?->format('Y-m-d') ?? '—' }}</td><td class="px-5 py-3">@if(!$batch->expiry_date)<span class="badge bg-slate-100 text-slate-600">{{ __('No expiry') }}</span>@elseif($batch->expiry_date->isBefore(today()))<span class="badge bg-rose-100 text-rose-700">{{ __('Expired') }}</span>@else<span class="badge bg-emerald-100 text-emerald-700">{{ __('Valid') }}</span>@endif</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">{{ __('No batches recorded for this product.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card p-6 lg:col-span-3">
            <h2 class="font-bold">{{ __('Stock by location') }}</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($product->stocks as $stock)
                    <div class="flex justify-between py-3"><span>{{ $stock->location->name }}</span><strong>{{ number_format($stock->quantity, 3) }} {{ $product->unit }}</strong></div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">{{ __('No stock recorded yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold">{{ __('Movement history') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Date') }}</th><th class="px-5 py-3 text-start">{{ __('Type') }}</th><th class="px-5 py-3 text-start">{{ __('Location') }}</th><th class="px-5 py-3 text-end">{{ __('Change') }}</th><th class="px-5 py-3 text-start">{{ __('User') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        @foreach ($movement->lines as $line)
                            <tr><td class="px-5 py-3">{{ $movement->occurred_at->format('Y-m-d H:i') }}</td><td class="px-5 py-3">{{ __(str_replace('_', ' ', ucfirst($movement->type))) }}</td><td class="px-5 py-3">{{ $line->location->name }}</td><td class="px-5 py-3 text-end font-bold {{ $line->quantity_delta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $line->quantity_delta >= 0 ? '+' : '' }}{{ $line->quantity_delta }}</td><td class="px-5 py-3 text-slate-500">{{ $movement->user?->name ?? __('System') }}</td></tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">{{ __('No movements yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
