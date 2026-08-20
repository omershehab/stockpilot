<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="badge bg-emerald-100 text-emerald-700">{{ __('One-time setup') }}</span>
            <h1 class="mt-3 text-2xl font-bold">{{ __('Set up :company', ['company' => $company->name]) }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Choose your currency and configure the first warehouse before entering the inventory workspace.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-[1fr_280px]">
        <section class="card p-6 sm:p-8">
            <form method="POST" action="{{ route('companies.onboarding.update', $company) }}" class="space-y-6" x-data="{ makeTeam: @js((bool) old('team_name')), inviteUser: @js((bool) old('invite_email')) }">
                @csrf
                @method('PATCH')

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <p class="font-bold">{{ __('Please review the setup details:') }}</p>
                        <ul class="mt-2 list-disc space-y-1 ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div>
                    <label class="label" for="currency">{{ __('Company currency') }}</label>
                    <select class="field" id="currency" name="currency" required autofocus>
                        @foreach ($currencies as $code => $name)
                            <option value="{{ $code }}" @selected(old('currency', $company->currency) === $code)>{{ $code }} — {{ __($name) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">{{ __('The Saudi Riyal vector will be used automatically whenever SAR is selected.') }}</p>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h2 class="font-bold">{{ __('First warehouse') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('You can add more warehouses and branches later.') }}</p>
                </div>

                <div>
                    <label class="label" for="warehouse_name">{{ __('Warehouse name') }}</label>
                    <input class="field" id="warehouse_name" name="warehouse_name" value="{{ old('warehouse_name', $warehouse->name) }}" placeholder="{{ __('Main Warehouse') }}" required>
                </div>

                <div>
                    <label class="label" for="warehouse_address">{{ __('Warehouse location') }}</label>
                    <input class="field" id="warehouse_address" name="warehouse_address" value="{{ old('warehouse_address', $warehouse->address) }}" placeholder="{{ __('City, district, street or building') }}" required>
                    <p class="mt-2 text-xs text-slate-500">{{ __('Enter a physical address or a clear internal location description.') }}</p>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h2 class="font-bold">{{ __('People & teamwork') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('These options are optional. You can manage more teams and invitations later.') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" x-model="makeTeam" class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span><strong class="block text-sm">{{ __('Create my first team') }}</strong><small class="text-slate-500">{{ __('Organize colleagues under one team from day one.') }}</small></span>
                    </label>
                    <div x-show="makeTeam" x-cloak class="mt-4">
                        <label class="label" for="team_name">{{ __('Team name') }}</label>
                        <input class="field" id="team_name" name="team_name" value="{{ old('team_name') }}" placeholder="{{ __('Warehouse Operations') }}" :disabled="!makeTeam" :required="makeTeam">
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" x-model="inviteUser" class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span><strong class="block text-sm">{{ __('Invite a colleague') }}</strong><small class="text-slate-500">{{ __('We will send a secure invitation valid for seven days.') }}</small></span>
                    </label>
                    <div x-show="inviteUser" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="label" for="invite_email">{{ __('Email address') }}</label><input class="field" id="invite_email" name="invite_email" type="email" value="{{ old('invite_email') }}" placeholder="colleague@company.com" :disabled="!inviteUser" :required="inviteUser"></div>
                        <div class="sm:col-span-2"><label class="label" for="invite_role">{{ __('Role') }}</label><select class="field" id="invite_role" name="invite_role" :disabled="!inviteUser" :required="inviteUser"><option value="admin" @selected(old('invite_role') === 'admin')>{{ __('Admin') }}</option><option value="manager" @selected(old('invite_role') === 'manager')>{{ __('Manager') }}</option><option value="staff" @selected(old('invite_role', 'staff') === 'staff')>{{ __('Staff') }}</option><option value="viewer" @selected(old('invite_role') === 'viewer')>{{ __('Viewer') }}</option></select></div>
                        <p x-show="makeTeam" class="sm:col-span-2 text-xs text-violet-700">{{ __('The invited colleague will automatically join the new team.') }}</p>
                    </div>
                </div>

                <button class="btn-primary w-full py-3">{{ __('Complete setup & open dashboard') }}</button>
            </form>
        </section>

        <aside class="card h-fit p-6">
            <h2 class="font-bold">{{ __('Setup progress') }}</h2>
            <ol class="mt-5 space-y-5 text-sm">
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700">✓</span><span><strong class="block">{{ __('Account created') }}</strong></span></li>
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-600 font-bold text-white">2</span><span><strong class="block">{{ __('Company setup') }}</strong><small class="text-slate-500">{{ __('Currency and warehouse') }}</small></span></li>
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 font-bold text-slate-500">3</span><span><strong class="block">{{ __('Build your team') }}</strong><small class="text-slate-500">{{ __('Create a team and invite a user') }}</small></span></li>
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 font-bold text-slate-500">4</span><span><strong class="block">{{ __('Add inventory') }}</strong></span></li>
            </ol>
            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-600">{{ __('This form appears only once. Company owners can update operational settings later from the company settings area.') }}</div>
        </aside>
    </div>
</x-app-layout>
