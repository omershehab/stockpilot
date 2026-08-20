<x-app-layout>
    <x-slot name="header">
        <div><a href="{{ route('companies.warehouses.index', $company) }}" class="text-sm font-semibold text-emerald-700 hover:underline">← {{ __('Back to warehouses') }}</a><h1 class="mt-2 text-2xl font-bold">{{ $warehouse->exists ? __('Edit warehouse') : __('Add warehouse') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Configure the warehouse identity, address, and operational status.') }}</p></div>
    </x-slot>

    <form method="POST" action="{{ $warehouse->exists ? route('companies.warehouses.update', [$company, $warehouse]) : route('companies.warehouses.store', $company) }}" class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,.7fr)]">
        @csrf
        @if($warehouse->exists) @method('PUT') @endif

        <section class="card p-6">
            <h2 class="font-bold">{{ __('Warehouse details') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Use a clear name and a short unique code for reports and transfers.') }}</p>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div><label class="label" for="name">{{ __('Warehouse name') }}</label><input class="field" id="name" name="name" value="{{ old('name', $warehouse->name) }}" placeholder="{{ __('North Riyadh Warehouse') }}" required autofocus></div>
                <div><label class="label" for="code">{{ __('Warehouse code') }}</label><input class="field font-mono uppercase" id="code" name="code" value="{{ old('code', $warehouse->code) }}" placeholder="NORTH-01" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required dir="ltr"><p class="mt-1 text-xs text-slate-400">{{ __('Letters, numbers, dashes, and underscores only.') }}</p></div>
                <div class="sm:col-span-2"><label class="label" for="address">{{ __('Warehouse location') }}</label><textarea class="field" id="address" name="address" rows="3" placeholder="{{ __('City, district, street or building') }}">{{ old('address', $warehouse->address) }}</textarea></div>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="card p-5"><h2 class="font-bold">{{ __('Warehouse settings') }}</h2>
                <div class="mt-5 space-y-4">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $warehouse->is_default)) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span><strong class="block text-sm">{{ __('Default warehouse') }}</strong><small class="mt-1 block text-slate-500">{{ __('Use as the primary location for this company.') }}</small></span></label>
                    @if($warehouse->exists)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $warehouse->is_active)) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span><strong class="block text-sm">{{ __('Active warehouse') }}</strong><small class="mt-1 block text-slate-500">{{ __('Allow stock movements and inventory counts at this location.') }}</small></span></label>
                    @endif
                </div>
                @if($warehouse->is_default)<p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs leading-5 text-amber-800">{{ __('Choose another default warehouse before deactivating this one.') }}</p>@endif
            </section>
            <div class="flex gap-3"><a href="{{ route('companies.warehouses.index', $company) }}" class="btn-secondary flex-1">{{ __('Cancel') }}</a><button class="btn-primary flex-1">{{ $warehouse->exists ? __('Save warehouse') : __('Create warehouse') }}</button></div>
        </aside>
    </form>
</x-app-layout>
