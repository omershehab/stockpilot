<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold">{{ __('Print labels') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Build a multi-product barcode print job with live preview.') }}</p></div>
            <a class="btn-secondary" href="{{ route('companies.label-templates.index', $company) }}">{{ __('Edit label designs') }}</a>
        </div>
    </x-slot>

    <div x-data="labelPrinter({{ Js::from($templates->map(fn ($template) => ['id' => $template->id, 'name' => $template->name, 'width' => (float) $template->width_mm, 'height' => (float) $template->height_mm, 'isDefault' => $template->is_default, 'canDelete' => $templates->count() > 1, 'editUrl' => route('companies.label-templates.index', [$company, 'template' => $template->id]), 'duplicateUrl' => route('companies.label-templates.duplicate', [$company, $template]), 'deleteUrl' => route('companies.label-templates.destroy', [$company, $template])])->values()) }}, {{ Js::from($products) }}, {{ Js::from(route('companies.labels.preview', $company)) }})" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(440px,0.9fr)]">
        <div class="space-y-6">
            <section class="card p-6">
                <div class="flex items-center justify-between gap-3"><div><h2 class="font-bold">{{ __('Label design') }}</h2><p class="mt-1 text-xs text-slate-500">{{ __('Choose one of your saved templates.') }}</p></div><a class="text-sm font-semibold text-emerald-700 hover:underline" href="{{ route('companies.label-templates.index', $company) }}">+ {{ __('New design') }}</a></div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="template in templates" :key="template.id">
                        <div role="button" tabindex="0" @click="selectedTemplateId=template.id" @keydown.enter.prevent="selectedTemplateId=template.id" @keydown.space.prevent="selectedTemplateId=template.id" class="group relative cursor-pointer rounded-2xl border-2 p-4 text-start transition" :class="selectedTemplateId===template.id ? 'border-emerald-500 bg-emerald-50 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                            <div class="absolute end-3 top-3 z-20 flex gap-1 rounded-lg bg-white/95 p-1 opacity-0 shadow-md ring-1 ring-slate-200 transition group-hover:opacity-100 group-focus:opacity-100 group-focus-within:opacity-100">
                                <form method="POST" :action="template.duplicateUrl" @click.stop>@csrf<button type="submit" class="grid h-8 w-8 place-items-center rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Duplicate template') }}" aria-label="{{ __('Duplicate template') }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="8" y="8" width="11" height="11" rx="2"></rect><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"></path></svg></button></form>
                                <a :href="template.editUrl" @click.stop class="grid h-8 w-8 place-items-center rounded-md text-slate-600 hover:bg-emerald-50 hover:text-emerald-700" title="{{ __('Edit template') }}" aria-label="{{ __('Edit template') }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13.5 6.5 17.5 10.5"></path><path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"></path></svg></a>
                                <form method="POST" :action="template.deleteUrl" @click.stop onsubmit='return confirm(@js(__("Delete this label design? This action cannot be undone.")))'>@csrf @method('DELETE')<button type="submit" :disabled="!template.canDelete" class="grid h-8 w-8 place-items-center rounded-md text-slate-600 hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-40" :title='template.canDelete ? @js(__("Delete template")) : @js(__("The final template cannot be deleted"))' aria-label="{{ __('Delete template') }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"></path></svg></button></form>
                            </div>
                            <div class="flex h-24 items-center justify-center rounded-xl bg-slate-100"><div class="relative overflow-hidden border bg-white shadow-sm" :style="templatePreviewStyle(template)"><span class="absolute inset-x-2 top-2 h-1 rounded bg-slate-800"></span><span class="absolute inset-x-2 bottom-3 h-7 bg-[repeating-linear-gradient(90deg,#0f172a_0,#0f172a_1px,transparent_1px,transparent_3px)]"></span></div></div>
                            <strong class="mt-3 block truncate text-sm" x-text="template.name"></strong><small class="mt-1 block text-slate-500"><span x-text="`${template.width} × ${template.height} mm`"></span><span x-show="template.isDefault"> · {{ __('Default') }}</span></small>
                        </div>
                    </template>
                </div>
            </section>

            <section class="card p-6">
                <div><h2 class="font-bold">{{ __('Items') }}</h2><p class="mt-1 text-xs text-slate-500">{{ __('Search products and choose how many labels to print.') }}</p></div>
                <div class="relative mt-5" @click.outside="searchOpen=false">
                    <label class="label" for="label-product-search">{{ __('Add product to print') }}</label>
                    <input id="label-product-search" class="field" type="search" x-model="search" @focus="searchOpen=true" @input="searchOpen=true" placeholder="{{ __('Search by product name, SKU, or barcode...') }}" autocomplete="off">
                    <div x-show="searchOpen && filteredProducts.length" x-cloak class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <template x-for="product in filteredProducts" :key="product.id"><button type="button" @click="addProduct(product)" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start hover:bg-emerald-50"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">▦</span><span class="min-w-0 flex-1"><strong class="block truncate text-sm" x-text="product.name"></strong><small class="block truncate text-slate-500" x-text="`${product.sku} · ${product.barcode}`"></small></span><span class="text-lg text-emerald-600">＋</span></button></template>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
                    <div class="grid grid-cols-[1fr_100px_40px] gap-3 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"><span>{{ __('Item') }}</span><span>{{ __('Copies') }}</span><span></span></div>
                    <div class="divide-y divide-slate-100">
                        <template x-for="item in items" :key="item.id"><div class="grid grid-cols-[1fr_100px_40px] items-center gap-3 px-4 py-3"><div class="min-w-0"><strong class="block truncate text-sm" x-text="item.name"></strong><small class="block truncate font-mono text-xs text-slate-500" x-text="`${item.sku} · ${item.barcode}`"></small></div><input class="field !py-1.5 text-center" type="number" min="1" max="100" x-model.number="item.copies" @change="normalizeCopies(item)"><button type="button" @click="removeProduct(item.id)" class="rounded-lg p-2 text-rose-500 hover:bg-rose-50" aria-label="{{ __('Remove product') }}">✕</button></div></template>
                        <div x-show="!items.length" class="px-4 py-12 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-xl text-slate-400">▦</span><p class="mt-3 text-sm font-medium text-slate-600">{{ __('No products added yet') }}</p><p class="mt-1 text-xs text-slate-400">{{ __('Use the search box above to start your print job.') }}</p></div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm"><span class="text-slate-500"><strong class="text-slate-900" x-text="items.length"></strong> {{ __('Items') }}</span><span class="text-slate-500"><strong class="text-slate-900" x-text="totalCopies"></strong> {{ __('Labels') }}</span></div>
            </section>

            <button type="button" class="btn-primary w-full !py-3" :disabled="!items.length" :class="!items.length ? 'cursor-not-allowed opacity-50' : ''" @click="openPrint">{{ __('Print labels') }}</button>
        </div>

        <aside class="xl:sticky xl:top-6 xl:self-start">
            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-4"><div><h2 class="font-bold">{{ __('Print preview') }}</h2><p class="mt-1 text-xs text-slate-500" x-text="selectedTemplate ? `${selectedTemplate.width} × ${selectedTemplate.height} mm · ${totalCopies} ${labelsWord}` : chooseTemplate"></p></div><button type="button" class="btn-secondary !py-2" :disabled="!items.length" @click="openPrint">{{ __('Open print view') }} ↗</button></div>
                <iframe class="h-[720px] w-full bg-slate-200" :src="previewUrl" title="{{ __('Print preview') }}"></iframe>
            </section>
        </aside>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => Alpine.data('labelPrinter', (templates,products,previewBaseUrl) => ({
            templates, products, previewBaseUrl, labelsWord: @js(__('Labels')), chooseTemplate: @js(__('Choose a template')), selectedTemplateId: templates[0]?.id ?? null, items: [], search: '', searchOpen: false,
            get selectedTemplate(){ return this.templates.find(template => template.id===this.selectedTemplateId) ?? null; },
            get totalCopies(){ return this.items.reduce((total,item) => total+(Number(item.copies)||0),0); },
            get filteredProducts(){
                const query=this.search.trim().toLowerCase();
                if(!query)return [];
                return this.products.filter(product => !this.items.some(item => item.id===product.id) && [product.name,product.nameAr,product.sku,product.barcode].filter(Boolean).some(value => String(value).toLowerCase().includes(query))).slice(0,12);
            },
            get previewUrl(){
                const url=new URL(this.previewBaseUrl);
                if(this.selectedTemplateId)url.searchParams.set('template',this.selectedTemplateId);
                url.searchParams.set('embedded','1');
                this.items.forEach(item => url.searchParams.set(`items[${item.id}]`,Math.min(100,Math.max(1,Number(item.copies)||1))));
                return url.toString();
            },
            templatePreviewStyle(template){ const ratio=template.width/template.height; const width=Math.min(150,70*ratio); return `width:${width}px;height:${width/ratio}px`; },
            addProduct(product){ this.items.push({...product,copies:1}); this.search=''; this.searchOpen=false; },
            removeProduct(id){ this.items=this.items.filter(item => item.id!==id); },
            normalizeCopies(item){ item.copies=Math.min(100,Math.max(1,Number(item.copies)||1)); },
            openPrint(){ if(!this.items.length)return; const url=new URL(this.previewUrl); url.searchParams.delete('embedded'); window.open(url.toString(),'_blank'); }
        })))
    </script>
    @endpush
</x-app-layout>
