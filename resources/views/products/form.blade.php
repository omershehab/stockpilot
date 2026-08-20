<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold">{{ $product->exists ? __('Edit product') : __('New product') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Product identity, tracking, image, pricing, and stock settings.') }}</p>
        </div>
    </x-slot>

    <div x-data="productForm" @keydown.escape.window="categoryModal = false; unitModal = false">
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ $product->exists ? route('companies.products.update', [$company, $product]) : route('companies.products.store', $company) }}"
            class="grid gap-6 xl:grid-cols-3"
        >
            @csrf
            @if ($product->exists)
                @method('PUT')
            @endif

            <div class="space-y-6 xl:col-span-2">
                <section class="card p-6">
                    <h2 class="mb-5 font-bold">{{ __('Product details') }}</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="label" for="name">{{ __('English name') }} *</label>
                            <input class="field" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="name_ar">{{ __('Arabic name') }}</label>
                            <input class="field" dir="rtl" id="name_ar" name="name_ar" value="{{ old('name_ar', $product->name_ar) }}">
                        </div>
                        <div>
                            <label class="label" for="sku">SKU *</label>
                            <input class="field" id="sku" name="sku" value="{{ old('sku', $product->sku ?: 'SKU-'.strtoupper(Str::random(6))) }}" required>
                            @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="barcode">{{ __('Primary barcode') }} *</label>
                            <div class="flex gap-2">
                                <input class="field" id="barcode" name="barcode" value="{{ old('barcode', $product->primary_barcode?->code ?: now()->format('ymd').random_int(100000, 999999)) }}" required>
                                <button type="button" class="btn-secondary whitespace-nowrap" onclick="document.getElementById('barcode').value = Date.now().toString().slice(-12)">{{ __('Generate') }}</button>
                            </div>
                            <small class="mt-1 block text-slate-500">{{ __('Code 128 supports letters and numbers.') }}</small>
                            @error('barcode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label class="text-sm font-medium text-slate-700" for="category_id">{{ __('Category') }}</label>
                                <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="openCategoryModal">+ {{ __('Add category') }}</button>
                            </div>
                            <select class="field" id="category_id" name="category_id" x-model="categoryId">
                                <option value="">{{ __('No category') }}</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="String(category.id)" x-text="optionLabel(category)"></option>
                                </template>
                            </select>
                            @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label class="text-sm font-medium text-slate-700" for="unit">{{ __('Unit') }}</label>
                                <button type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="openUnitModal">+ {{ __('Add unit') }}</button>
                            </div>
                            <select class="field" id="unit" name="unit" x-model="unit" required>
                                <template x-for="item in units" :key="item.id">
                                    <option :value="item.symbol" x-text="unitLabel(item)"></option>
                                </template>
                            </select>
                            @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="description">{{ __('Description') }}</label>
                            <textarea class="field" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold">{{ __('Batches & expiry dates') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('A product can have multiple batches. Each batch has its own expiry date.') }}</p>
                        </div>
                        <button type="button" class="btn-secondary" @click="addBatch">+ {{ __('Add batch') }}</button>
                    </div>

                    <div x-show="batches.length === 0" class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        {{ __('No batches added. Use “Add batch” when this product is batch-tracked.') }}
                    </div>

                    <div class="mt-5 space-y-3">
                        <template x-for="(batch, index) in batches" :key="batch.key">
                            <div class="grid items-end gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[1fr_1fr_auto]">
                                <div>
                                    <label class="label" :for="'batch_number_' + index">{{ __('Batch number') }} *</label>
                                    <input class="field bg-white" :id="'batch_number_' + index" :name="'batches[' + index + '][batch_number]'" x-model="batch.batch_number" maxlength="100" placeholder="e.g. LOT-2026-001" required>
                                </div>
                                <div>
                                    <label class="label" :for="'expiry_date_' + index">{{ __('Expiry date') }}</label>
                                    <input class="field bg-white" type="date" :id="'expiry_date_' + index" :name="'batches[' + index + '][expiry_date]'" x-model="batch.expiry_date">
                                </div>
                                <button type="button" class="btn-secondary text-rose-600 hover:bg-rose-50" @click="removeBatch(index)" :aria-label='@js(__('Remove')) + " " + (index + 1)'>{{ __('Remove') }}</button>
                            </div>
                        </template>
                    </div>
                    @if ($errors->has('batches.*.batch_number')) <p class="mt-2 text-xs text-rose-600">{{ $errors->first('batches.*.batch_number') }}</p> @endif
                    @if ($errors->has('batches.*.expiry_date')) <p class="mt-2 text-xs text-rose-600">{{ $errors->first('batches.*.expiry_date') }}</p> @endif
                </section>
            </div>

            <aside class="space-y-6">
                <section class="card p-6">
                    <h2 class="font-bold">{{ __('Product image') }}</h2>
                    <div class="mt-4 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                        <img x-show="imagePreview" :src="imagePreview" alt="{{ __('Product image preview') }}" class="h-full w-full object-contain">
                        <div x-show="!imagePreview" class="px-4 text-center text-sm text-slate-400">
                            <svg class="mx-auto mb-2 h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Z" /></svg>
                            {{ __('No image selected') }}
                        </div>
                    </div>
                    <label class="btn-secondary mt-4 w-full cursor-pointer" for="image">{{ __('Choose image') }}</label>
                    <input class="sr-only" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" @change="previewImage">
                    <p class="mt-2 text-xs text-slate-500">{{ __('JPG, PNG, or WebP up to 5 MB.') }}</p>
                    @error('image') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </section>

                <section class="card p-6">
                    <h2 class="mb-5 font-bold">{{ __('Pricing & stock') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="label" for="cost">{{ __('Cost') }} (<x-currency-symbol :currency="$company->currency" />)</label>
                            <input class="field" type="number" min="0" step="0.01" id="cost" name="cost" value="{{ old('cost', $product->cost ?? 0) }}" required>
                        </div>
                        <div>
                            <label class="label" for="price">{{ __('Selling price') }}</label>
                            <input class="field" type="number" min="0" step="0.01" id="price" name="price" value="{{ old('price', $product->price ?? 0) }}" required>
                        </div>
                        <div>
                            <label class="label" for="minimum_stock">{{ __('Minimum stock') }}</label>
                            <input class="field" type="number" min="0" step="0.001" id="minimum_stock" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}" required>
                        </div>
                        <label class="flex items-center gap-3 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))>
                            {{ __('Active product') }}
                        </label>
                    </div>
                </section>

                <div class="flex gap-3">
                    <a class="btn-secondary flex-1" href="{{ route('companies.products.index', $company) }}">{{ __('Cancel') }}</a>
                    <button class="btn-primary flex-1">{{ __('Save product') }}</button>
                </div>
            </aside>
        </form>

        <div x-cloak x-show="categoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <button type="button" class="absolute inset-0 bg-slate-950/50" aria-label="{{ __('Close') }}" @click="categoryModal = false"></button>
            <section x-show="categoryModal" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div><h2 id="category-modal-title" class="text-lg font-bold">{{ __('Add category') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('The new category will be selected immediately.') }}</p></div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="categoryModal = false" aria-label="{{ __('Close') }}">✕</button>
                </div>
                <div class="mt-5 space-y-4" @keydown.enter.prevent="createCategory">
                    <div><label class="label" for="new_category_name">{{ __('English name') }} *</label><input class="field" id="new_category_name" x-ref="categoryName" x-model="categoryDraft.name" maxlength="120"></div>
                    <div><label class="label" for="new_category_name_ar">{{ __('Arabic name') }}</label><input class="field" dir="rtl" id="new_category_name_ar" x-model="categoryDraft.name_ar" maxlength="120"></div>
                    <p x-show="categoryError" x-text="categoryError" class="text-sm text-rose-600"></p>
                </div>
                <div class="mt-6 flex justify-end gap-3"><button type="button" class="btn-secondary" @click="categoryModal = false">{{ __('Cancel') }}</button><button type="button" class="btn-primary" :disabled="categorySaving" @click="createCategory"><span x-text='categorySaving ? @js(__('Saving…')) : @js(__('Add category'))'></span></button></div>
            </section>
        </div>

        <div x-cloak x-show="unitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="unit-modal-title">
            <button type="button" class="absolute inset-0 bg-slate-950/50" aria-label="{{ __('Close') }}" @click="unitModal = false"></button>
            <section x-show="unitModal" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div><h2 id="unit-modal-title" class="text-lg font-bold">{{ __('Add unit') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Create a reusable unit for this company.') }}</p></div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="unitModal = false" aria-label="{{ __('Close') }}">✕</button>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2" @keydown.enter.prevent="createUnit">
                    <div><label class="label" for="new_unit_name">{{ __('English name') }} *</label><input class="field" id="new_unit_name" x-ref="unitName" x-model="unitDraft.name" maxlength="80" placeholder="Carton"></div>
                    <div><label class="label" for="new_unit_name_ar">{{ __('Arabic name') }}</label><input class="field" dir="rtl" id="new_unit_name_ar" x-model="unitDraft.name_ar" maxlength="80"></div>
                    <div class="sm:col-span-2"><label class="label" for="new_unit_symbol">{{ __('Symbol') }} *</label><input class="field" id="new_unit_symbol" x-model="unitDraft.symbol" maxlength="30" placeholder="ctn"><p class="mt-1 text-xs text-slate-500">{{ __('Letters, numbers, dots, underscores, and dashes.') }}</p></div>
                    <p x-show="unitError" x-text="unitError" class="text-sm text-rose-600 sm:col-span-2"></p>
                </div>
                <div class="mt-6 flex justify-end gap-3"><button type="button" class="btn-secondary" @click="unitModal = false">{{ __('Cancel') }}</button><button type="button" class="btn-primary" :disabled="unitSaving" @click="createUnit"><span x-text='unitSaving ? @js(__('Saving…')) : @js(__('Add unit'))'></span></button></div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productForm', () => ({
                locale: @js(app()->getLocale()),
                categories: @js($categories->map->only(['id', 'name', 'name_ar'])->values()),
                units: @js($units->map->only(['id', 'name', 'name_ar', 'symbol'])->values()),
                batches: @js(collect(old('batches', $product->exists ? $product->batches->map(fn ($batch) => ['batch_number' => $batch->batch_number, 'expiry_date' => $batch->expiry_date?->format('Y-m-d')])->all() : []))->values()),
                categoryId: @js((string) old('category_id', $product->category_id ?? '')),
                unit: @js((string) old('unit', $product->unit ?: ($units->contains('symbol', 'pcs') ? 'pcs' : ($units->first()?->symbol ?? 'pcs')))),
                imagePreview: @js($product->image_path ? asset('storage/'.$product->image_path) : null),
                categoryModal: false,
                unitModal: false,
                categorySaving: false,
                unitSaving: false,
                categoryError: '',
                unitError: '',
                categoryDraft: { name: '', name_ar: '' },
                unitDraft: { name: '', name_ar: '', symbol: '' },
                batchSequence: 0,

                init() {
                    this.batches = this.batches.map(batch => ({ ...batch, key: ++this.batchSequence }));
                },

                optionLabel(item) {
                    return this.locale === 'ar' && item.name_ar ? item.name_ar : item.name;
                },
                unitLabel(item) {
                    return this.optionLabel(item) + ' (' + item.symbol + ')';
                },
                openCategoryModal() {
                    this.categoryError = '';
                    this.categoryModal = true;
                    this.$nextTick(() => this.$refs.categoryName.focus());
                },
                openUnitModal() {
                    this.unitError = '';
                    this.unitModal = true;
                    this.$nextTick(() => this.$refs.unitName.focus());
                },
                previewImage(event) {
                    const file = event.target.files[0];
                    if (file) this.imagePreview = URL.createObjectURL(file);
                },
                addBatch() {
                    this.batches.push({ key: ++this.batchSequence, batch_number: '', expiry_date: '' });
                    this.$nextTick(() => document.getElementById('batch_number_' + (this.batches.length - 1))?.focus());
                },
                removeBatch(index) {
                    this.batches.splice(index, 1);
                },
                firstError(payload, fallback) {
                    const errors = payload?.errors || {};
                    return Object.values(errors).flat()[0] || payload?.message || fallback;
                },
                async createCategory() {
                    if (!this.categoryDraft.name.trim()) {
                        this.categoryError = @js(__('Category name is required.'));
                        return;
                    }
                    this.categorySaving = true;
                    this.categoryError = '';
                    try {
                        const response = await fetch(@js(route('companies.categories.store', $company)), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify(this.categoryDraft),
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(this.firstError(payload, 'Could not create category.'));
                        this.categories.push(payload.category);
                        this.categories.sort((a, b) => a.name.localeCompare(b.name));
                        this.categoryId = String(payload.category.id);
                        this.categoryDraft = { name: '', name_ar: '' };
                        this.categoryModal = false;
                    } catch (error) {
                        this.categoryError = error.message;
                    } finally {
                        this.categorySaving = false;
                    }
                },
                async createUnit() {
                    if (!this.unitDraft.name.trim() || !this.unitDraft.symbol.trim()) {
                        this.unitError = @js(__('Unit name and symbol are required.'));
                        return;
                    }
                    this.unitSaving = true;
                    this.unitError = '';
                    try {
                        const response = await fetch(@js(route('companies.units.store', $company)), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify(this.unitDraft),
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(this.firstError(payload, 'Could not create unit.'));
                        this.units.push(payload.unit);
                        this.units.sort((a, b) => a.name.localeCompare(b.name));
                        this.unit = payload.unit.symbol;
                        this.unitDraft = { name: '', name_ar: '', symbol: '' };
                        this.unitModal = false;
                    } catch (error) {
                        this.unitError = error.message;
                    } finally {
                        this.unitSaving = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
