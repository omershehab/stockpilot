<x-app-layout>
    @php
        $canManage = in_array('products.manage', $currentPermissions, true);
        $limitReached = $company->hasReachedProductLimit($productCount);
        $remainingSlots = $company->product_limit === null ? null : max(0, $company->product_limit - $productCount);
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold">{{ __('Products') }}</h1>
                    @if($company->product_limit !== null)
                        <span class="badge {{ $limitReached ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ __(':used of :limit used', ['used' => $productCount, 'limit' => $company->product_limit]) }}</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ __('Search, filter, and maintain your complete product catalog.') }}</p>
            </div>
            @if($canManage)
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="btn-secondary gap-2" @click="$dispatch('open-product-import')" @disabled($limitReached)><span aria-hidden="true">⇧</span>{{ __('Bulk import') }}</button>
                    @if($limitReached)
                        <span class="badge bg-amber-100 text-amber-800">{{ __('Product limit reached') }}</span>
                    @else
                        <a href="{{ route('companies.products.create', $company) }}" class="btn-primary gap-2"><span class="text-lg leading-none">+</span>{{ __('Add product') }}</a>
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div x-data="{ importOpen: {{ ($errors->has('import') || $errors->has('import_file')) ? 'true' : 'false' }} }" @open-product-import.window="importOpen = true">
        <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach([
                ['url' => route('companies.products.index', $company), 'icon' => '▦', 'iconClass' => 'bg-emerald-100 text-emerald-700', 'count' => $productCount, 'label' => __('All products'), 'hint' => __('Complete catalog')],
                ['url' => route('companies.products.index', [$company, 'status' => 'active']), 'icon' => '✓', 'iconClass' => 'bg-blue-100 text-blue-700', 'count' => $activeCount, 'label' => __('Active products'), 'hint' => __('Available for operations')],
                ['url' => route('companies.products.index', [$company, 'stock' => 'low']), 'icon' => '!', 'iconClass' => 'bg-amber-100 text-amber-700', 'count' => max(0, $lowStockCount - $outOfStockCount), 'label' => __('Low stock'), 'hint' => __('At or below minimum')],
                ['url' => route('companies.products.index', [$company, 'stock' => 'out']), 'icon' => '0', 'iconClass' => 'bg-rose-100 text-rose-700', 'count' => $outOfStockCount, 'label' => __('Out of stock'), 'hint' => __('Needs replenishment')],
                ['url' => route('companies.products.index', [$company, 'expiry' => 'soon']), 'icon' => '⌛', 'iconClass' => 'bg-violet-100 text-violet-700', 'count' => $expiringCount, 'label' => __('Expiring soon'), 'hint' => __('Within 30 days')],
            ] as $metric)
                <a href="{{ $metric['url'] }}" class="card p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md {{ $loop->last ? 'col-span-2 lg:col-span-1' : '' }}">
                    <div class="flex items-center justify-between gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl font-bold {{ $metric['iconClass'] }}">{{ $metric['icon'] }}</span><span class="text-2xl font-black">{{ number_format($metric['count']) }}</span></div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">{{ $metric['label'] }}</p><small class="mt-1 block text-slate-400">{{ $metric['hint'] }}</small>
                </a>
            @endforeach
        </section>

        <section class="card mb-5 overflow-hidden">
            <form method="GET" action="{{ route('companies.products.index', $company) }}" class="p-4 sm:p-5">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="sm:col-span-2 lg:col-span-2"><label class="label" for="product-search">{{ __('Search catalog') }}</label><div class="relative"><span class="absolute start-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span><input class="field ps-10" id="product-search" type="search" name="search" value="{{ $search }}" placeholder="{{ __('Name, SKU, or barcode') }}"></div></div>
                    <div><label class="label" for="category-filter">{{ __('Category') }}</label><select class="field" id="category-filter" name="category"><option value="">{{ __('All categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($filters['categoryId'] === $category->id)>{{ app()->isLocale('ar') && $category->name_ar ? $category->name_ar : $category->name }}</option>@endforeach</select></div>
                    <div><label class="label" for="status-filter">{{ __('Status') }}</label><select class="field" id="status-filter" name="status"><option value="">{{ __('Any status') }}</option><option value="active" @selected($filters['status'] === 'active')>{{ __('Active') }}</option><option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('Inactive') }}</option></select></div>
                    <div><label class="label" for="stock-filter">{{ __('Stock level') }}</label><select class="field" id="stock-filter" name="stock"><option value="">{{ __('Any stock level') }}</option><option value="in_stock" @selected($filters['stock'] === 'in_stock')>{{ __('In stock') }}</option><option value="low" @selected($filters['stock'] === 'low')>{{ __('Low stock') }}</option><option value="out" @selected($filters['stock'] === 'out')>{{ __('Out of stock') }}</option></select></div>
                    <div><label class="label" for="expiry-filter">{{ __('Expiry') }}</label><select class="field" id="expiry-filter" name="expiry"><option value="">{{ __('Any expiry') }}</option><option value="soon" @selected($filters['expiry'] === 'soon')>{{ __('Expiring in 30 days') }}</option><option value="expired" @selected($filters['expiry'] === 'expired')>{{ __('Expired') }}</option><option value="none" @selected($filters['expiry'] === 'none')>{{ __('No expiry date') }}</option></select></div>
                </div>
                <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
                    <div class="w-full sm:w-auto"><label class="label" for="sort-filter">{{ __('Sort by') }}</label><select class="field min-w-48" id="sort-filter" name="sort"><option value="latest" @selected($filters['sort'] === 'latest')>{{ __('Newest first') }}</option><option value="name" @selected($filters['sort'] === 'name')>{{ __('Name A–Z') }}</option><option value="stock_low" @selected($filters['sort'] === 'stock_low')>{{ __('Stock: low to high') }}</option><option value="stock_high" @selected($filters['sort'] === 'stock_high')>{{ __('Stock: high to low') }}</option><option value="price_low" @selected($filters['sort'] === 'price_low')>{{ __('Price: low to high') }}</option><option value="price_high" @selected($filters['sort'] === 'price_high')>{{ __('Price: high to low') }}</option></select></div>
                    <div class="flex w-full gap-2 sm:w-auto">@if($filterCount || $filters['sort'] !== 'latest')<a href="{{ route('companies.products.index', $company) }}" class="btn-secondary flex-1 sm:flex-none">{{ __('Clear filters') }}</a>@endif<button class="btn-primary flex-1 sm:flex-none">{{ __('Apply filters') }}@if($filterCount)<span class="ms-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ $filterCount }}</span>@endif</button></div>
                </div>
            </form>
        </section>

        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 sm:px-5">
                <div><strong class="text-sm">{{ trans_choice('{0} No products found|{1} :count product found|[2,*] :count products found', $products->total(), ['count' => number_format($products->total())]) }}</strong>@if($filterCount)<span class="ms-2 text-xs text-slate-400">{{ __('Filtered from :total', ['total' => number_format($productCount)]) }}</span>@endif</div>
                @if($canManage && !$limitReached)<a href="{{ route('companies.products.create', $company) }}" class="text-sm font-semibold text-emerald-700 hover:underline">+ {{ __('Quick add') }}</a>@endif
            </div>

            <div class="hidden overflow-x-auto sm:block"><table class="w-full min-w-[840px] text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Product') }}</th><th class="px-5 py-3 text-start">{{ __('SKU / Barcode') }}</th><th class="px-5 py-3 text-start">{{ __('Category') }}</th><th class="px-5 py-3 text-end">{{ __('On hand') }}</th><th class="px-5 py-3 text-end">{{ __('Price') }}</th><th class="px-5 py-3 text-end">{{ __('Actions') }}</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    @php
                        $quantity = (float) ($product->stocks_sum_quantity ?? 0);
                        $batch = $product->batches->first();
                        $stockState = $quantity <= 0 ? 'out' : ($quantity <= (float) $product->minimum_stock ? 'low' : 'ok');
                    @endphp
                    <tr class="group hover:bg-slate-50">
                        <td class="px-5 py-4"><div class="flex items-center gap-3">@if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="" class="h-11 w-11 rounded-xl border border-slate-200 object-cover">@else<div class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-slate-100 font-bold text-slate-400">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</div>@endif<div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><a class="font-semibold text-slate-900 hover:text-emerald-700" href="{{ route('companies.products.show', [$company, $product]) }}">{{ app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name }}</a><span class="badge {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->is_active ? __('Active') : __('Inactive') }}</span></div>@if(app()->isLocale('en') && $product->name_ar)<small class="mt-1 block truncate text-slate-500" dir="rtl">{{ $product->name_ar }}</small>@endif @if($batch)<small class="mt-1 block text-slate-400">{{ __('Batch: :batch', ['batch' => $batch->batch_number]) }}@if($batch->expiry_date) · <span class="{{ $batch->expiry_date->isBefore(today()) ? 'font-semibold text-rose-600' : ($batch->expiry_date->lte(today()->addDays(30)) ? 'font-semibold text-violet-600' : '') }}">{{ __('Exp: :date', ['date' => $batch->expiry_date->format('Y-m-d')]) }}</span>@endif</small>@endif</div></div></td>
                        <td class="px-5 py-4"><code class="rounded-lg bg-slate-100 px-2 py-1 text-xs">{{ $product->sku }}</code><small class="mt-2 block font-mono text-slate-500">{{ $product->primary_barcode?->code ?? '—' }}</small></td>
                        <td class="px-5 py-4 text-slate-500">{{ $product->category ? (app()->isLocale('ar') && $product->category->name_ar ? $product->category->name_ar : $product->category->name) : '—' }}<small class="mt-1 block text-slate-400">{{ $product->unit }}</small></td>
                        <td class="px-5 py-4 text-end"><strong class="{{ $stockState === 'out' ? 'text-rose-600' : ($stockState === 'low' ? 'text-amber-600' : 'text-slate-900') }}">{{ number_format($quantity, 3) }}</strong><small class="mt-1 block {{ $stockState === 'ok' ? 'text-emerald-600' : ($stockState === 'low' ? 'text-amber-600' : 'text-rose-600') }}">{{ $stockState === 'ok' ? __('Healthy') : ($stockState === 'low' ? __('Low stock') : __('Out of stock')) }}</small></td>
                        <td class="px-5 py-4 text-end"><strong>{{ number_format($product->price, 2) }}</strong> <x-currency-symbol :currency="$company->currency" /><small class="mt-1 block text-slate-400">{{ __('Cost: :cost', ['cost' => number_format($product->cost, 2)]) }}</small></td>
                        <td class="px-5 py-4 text-end"><div class="flex justify-end gap-3"><a class="font-semibold text-slate-600 hover:text-emerald-700" href="{{ route('companies.products.show', [$company, $product]) }}">{{ __('View') }}</a>@if($canManage)<a class="font-semibold text-emerald-700 hover:underline" href="{{ route('companies.products.edit', [$company, $product]) }}">{{ __('Edit') }}</a>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-16 text-center"><span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400">⌕</span><strong class="mt-4 block text-slate-700">{{ $filterCount ? __('No products match these filters') : __('No products yet') }}</strong><p class="mx-auto mt-1 max-w-md text-sm text-slate-400">{{ $filterCount ? __('Try clearing one or more filters, or search with a different term.') : __('Add products one by one or import your catalog from the sample spreadsheet.') }}</p>@if($filterCount)<a class="mt-3 inline-block text-sm font-semibold text-emerald-700" href="{{ route('companies.products.index', $company) }}">{{ __('Clear filters') }}</a>@elseif($canManage && !$limitReached)<button type="button" class="mt-3 text-sm font-semibold text-emerald-700 hover:underline" @click="importOpen = true">{{ __('Import your first products') }}</button>@endif</td></tr>
                @endforelse
                </tbody></table></div>

            <div class="divide-y divide-slate-100 sm:hidden">
                @forelse($products as $product)
                    @php $quantity = (float) ($product->stocks_sum_quantity ?? 0); $batch = $product->batches->first(); @endphp
                    <article class="p-4"><div class="flex items-start gap-3"><div class="grid h-11 w-11 flex-none place-items-center rounded-xl bg-slate-100 font-bold text-slate-400">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</div><div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><a class="font-bold text-slate-900" href="{{ route('companies.products.show', [$company, $product]) }}">{{ app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name }}</a><span class="badge {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->is_active ? __('Active') : __('Inactive') }}</span></div><div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400"><code>{{ $product->sku }}</code><span>·</span><span class="font-mono">{{ $product->primary_barcode?->code ?? '—' }}</span></div></div></div><div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center"><div><small class="block text-slate-400">{{ __('On hand') }}</small><strong class="mt-1 block {{ $quantity <= (float) $product->minimum_stock ? 'text-amber-600' : '' }}">{{ number_format($quantity, 3) }}</strong></div><div class="border-x border-slate-200"><small class="block text-slate-400">{{ __('Price') }}</small><strong class="mt-1 block">{{ number_format($product->price, 2) }}</strong></div><div><small class="block text-slate-400">{{ __('Category') }}</small><strong class="mt-1 block truncate text-xs">{{ $product->category ? (app()->isLocale('ar') && $product->category->name_ar ? $product->category->name_ar : $product->category->name) : '—' }}</strong></div></div>@if($batch)<p class="mt-3 text-xs text-slate-400">{{ __('Batch: :batch', ['batch' => $batch->batch_number]) }}@if($batch->expiry_date) · {{ __('Exp: :date', ['date' => $batch->expiry_date->format('Y-m-d')]) }}@endif</p>@endif<div class="mt-3 flex gap-2"><a class="btn-secondary flex-1 !py-2" href="{{ route('companies.products.show', [$company, $product]) }}">{{ __('View') }}</a>@if($canManage)<a class="btn-primary flex-1 !py-2" href="{{ route('companies.products.edit', [$company, $product]) }}">{{ __('Edit') }}</a>@endif</div></article>
                @empty
                    <div class="p-10 text-center"><strong class="text-slate-600">{{ $filterCount ? __('No products match these filters') : __('No products yet') }}</strong></div>
                @endforelse
            </div>
            @if($products->hasPages())<div class="border-t border-slate-200 p-4">{{ $products->links() }}</div>@endif
        </section>

        @if($canManage)
            <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="import-title" @keydown.escape.window="importOpen = false">
                <div class="max-h-[94vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl" @click.outside="importOpen = false">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 sm:p-6"><div><span class="mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-emerald-100 text-xl text-emerald-700">⇧</span><h2 id="import-title" class="text-xl font-bold">{{ __('Bulk product import') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Upload CSV or XLSX. Every row is checked before anything is saved.') }}</p></div><button type="button" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="importOpen = false" aria-label="{{ __('Close') }}">✕</button></div>
                    <div class="space-y-5 p-5 sm:p-6">
                        @if($errors->has('import') || $errors->has('import_file'))<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><strong class="block">{{ __('Fix these import issues') }}</strong><ul class="mt-2 list-disc space-y-1 ps-5">@foreach(array_merge($errors->get('import'), $errors->get('import_file')) as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                        <div class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between"><div><strong class="text-sm text-emerald-900">{{ __('Start with the sample template') }}</strong><p class="mt-1 text-xs text-emerald-700">{{ __('It includes examples, accepted values, and warehouse stock columns.') }}</p></div><a class="btn-secondary flex-none gap-2 !border-emerald-300 !bg-white" href="{{ route('companies.products.import.template', $company) }}"><span>↓</span>{{ __('Download XLSX template') }}</a></div>
                        <div class="rounded-2xl border border-slate-200 p-4"><h3 class="text-sm font-bold">{{ __('Before you upload') }}</h3><div class="mt-3 grid gap-3 text-xs text-slate-500 sm:grid-cols-2"><p><span class="font-semibold text-slate-700">{{ __('Required:') }}</span> sku, name, barcode, unit, cost, price</p><p><span class="font-semibold text-slate-700">{{ __('Optional:') }}</span> name_ar, category, minimum_stock, batch_number, expiry_date, initial_quantity, warehouse_code, is_active</p><p>{{ __('Dates must use YYYY-MM-DD.') }}</p><p>{{ __('Categories not found in your company will be created automatically.') }}</p><p>{{ __('Use an existing unit symbol, such as pcs, box, or kg.') }}</p><p>{{ __('Warehouse code is required only when initial quantity is greater than zero.') }}</p></div></div>
                        <form method="POST" action="{{ route('companies.products.import.store', $company) }}" enctype="multipart/form-data" class="space-y-4">@csrf
                            <label class="group block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 p-7 text-center transition hover:border-emerald-400 hover:bg-emerald-50/60"><span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-700">＋</span><strong class="mt-3 block text-sm">{{ __('Choose CSV or XLSX file') }}</strong><span class="mt-1 block text-xs text-slate-400">{{ __('Maximum file size: 5 MB · Up to 1,000 products') }}</span><input class="mt-4 block w-full text-sm text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700" type="file" name="import_file" accept=".csv,.xlsx" required></label>
                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-400">@if($remainingSlots === null){{ __('Your plan has no product limit.') }}@else{{ trans_choice('{0} No product slots remain.|{1} :count product slot remains.|[2,*] :count product slots remain.', $remainingSlots, ['count' => $remainingSlots]) }}@endif</p><div class="flex gap-2"><button type="button" class="btn-secondary flex-1" @click="importOpen = false">{{ __('Cancel') }}</button><button class="btn-primary flex-1 gap-2" @disabled($limitReached)><span>⇧</span>{{ __('Validate and import') }}</button></div></div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
