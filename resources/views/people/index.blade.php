<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Users & teams') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('Control who can enter this workspace and what they can do.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge bg-emerald-100 text-emerald-800">{{ $members->where('pivot.status', 'active')->count() }} {{ __('Active') }}</span>
                <span class="badge bg-violet-100 text-violet-800">{{ $teams->count() }} {{ __('Teams') }}</span>
            </div>
        </div>
    </x-slot>

    @if (session('temporary_password'))
        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-bold">{{ __('New account sign-in details') }}</p>
                    <p class="mt-1 text-sm">{{ __('Share these details securely. This password is displayed only once.') }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm" dir="ltr">
                    <div><span class="text-slate-500">{{ __('Email') }}:</span> <strong>{{ session('new_user_email') }}</strong></div>
                    <div class="mt-1"><span class="text-slate-500">{{ __('Password') }}:</span> <code class="select-all font-bold">{{ session('temporary_password') }}</code></div>
                </div>
            </div>
        </div>
    @endif

    <div x-data="{ tab: '{{ request('tab', 'users') }}' }">
        <div class="mb-6 flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            @foreach ([['users', 'Users'], ['teams', 'Teams'], ['access', 'Access guide']] as [$key, $label])
                <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="min-w-max rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                    {{ __($label) }}
                </button>
            @endforeach
        </div>

        <section x-show="tab === 'users'" x-cloak class="space-y-6">
            @if($company->hasReachedUserLimit($members->count()))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900"><strong class="block">{{ __('User limit reached') }}</strong><span class="mt-1 block">{{ __('This company is using :used of :limit user accounts. Contact the platform administrator to increase the limit.', ['used' => $members->count(), 'limit' => $company->user_limit]) }}</span></div>
            @else
            <details class="card group" @if($members->count() === 1) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                    <div><h2 class="font-bold">+ {{ __('Add user') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Add an existing account by email or create a new sign-in.') }}</p></div>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-xl transition group-open:rotate-45">+</span>
                </summary>
                <form method="POST" action="{{ route('companies.people.members.store', $company) }}" class="border-t border-slate-200 p-5" x-data="{ accessMode: '{{ old('access_mode', 'role') }}', role: '{{ old('role', 'staff') }}' }">
                    @csrf
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <div><label class="label">{{ __('Full name') }}</label><input class="field" name="name" value="{{ old('name') }}" required></div>
                        <div><label class="label">{{ __('Email') }}</label><input class="field" type="email" name="email" value="{{ old('email') }}" required dir="ltr"></div>
                        <div><label class="label">{{ __('Temporary password') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label><input class="field" type="password" name="password" minlength="8" autocomplete="new-password" dir="ltr"><p class="mt-1 text-xs text-slate-400">{{ __('A secure password is generated if left blank.') }}</p></div>
                        <div><label class="label">{{ __('Role') }}</label><select class="field" name="role" x-model="role">@foreach($roles as $key => [$english, $arabic]) @continue($key === 'owner') <option value="{{ $key }}">{{ app()->isLocale('ar') ? $arabic : $english }}</option>@endforeach</select></div>
                        <div><label class="label">{{ __('Status') }}</label><select class="field" name="status"><option value="active">{{ __('Active') }}</option><option value="inactive">{{ __('Inactive') }}</option></select></div>
                        <div><label class="label">{{ __('Access source') }}</label><select class="field" name="access_mode" x-model="accessMode"><option value="role">{{ __('Use role preset') }}</option><option value="custom">{{ __('Custom access') }}</option></select></div>
                    </div>

                    @if($teams->isNotEmpty())
                        <div class="mt-5"><span class="label">{{ __('Teams') }}</span><div class="flex flex-wrap gap-2">@foreach($teams as $team)<label class="cursor-pointer"><input type="checkbox" name="team_ids[]" value="{{ $team->id }}" class="peer sr-only"><span class="inline-flex rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-800">{{ $team->name }}</span></label>@endforeach</div></div>
                    @endif

                    <div x-show="accessMode === 'custom'" x-transition class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="mb-4 text-sm font-semibold">{{ __('Choose individual permissions') }}</p>
                        @include('people.partials.permissions', ['selectedPermissions' => old('permissions', [])])
                    </div>
                    <div x-show="accessMode === 'role'" class="mt-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        {{ __('This user receives the selected role preset plus permissions inherited from teams.') }}
                    </div>
                    <div class="mt-5 flex justify-end"><button class="btn-primary">{{ __('Add user') }}</button></div>
                </form>
            </details>
            @endif

            <div class="card overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold">{{ __('Company users') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __(':count total accounts', ['count' => $members->count()]) }}</p></div>
                <div class="divide-y divide-slate-100">
                    @foreach($members as $member)
                        @php
                            $rawPersonal = $member->pivot->permissions;
                            $personalPermissions = is_string($rawPersonal) ? (json_decode($rawPersonal, true) ?: []) : ($rawPersonal ?? []);
                            $usesRolePreset = is_null($rawPersonal);
                            $roleLabel = $roles[$member->pivot->role] ?? [ucfirst($member->pivot->role), ''];
                        @endphp
                        <details class="group">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-4 px-5 py-4 hover:bg-slate-50">
                                <div class="grid h-11 w-11 flex-none place-items-center rounded-full bg-slate-900 font-bold text-white">{{ strtoupper(mb_substr($member->name, 0, 1)) }}</div>
                                <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><strong>{{ $member->name }}</strong>@if($member->id === Auth::id())<span class="badge bg-blue-100 text-blue-700">{{ __('You') }}</span>@endif @if($member->pivot->role === 'owner')<span class="badge bg-amber-100 text-amber-800">{{ __('Owner') }}</span>@endif</div><p class="truncate text-sm text-slate-500" dir="ltr">{{ $member->email }}</p></div>
                                <div class="hidden min-w-32 sm:block"><p class="text-sm font-semibold">{{ app()->isLocale('ar') ? $roleLabel[1] : $roleLabel[0] }}</p><p class="text-xs text-slate-400">{{ $usesRolePreset ? __('Role preset') : __('Custom access') }}</p></div>
                                <div class="hidden min-w-40 lg:block"><div class="flex -space-x-1 rtl:space-x-reverse">@forelse($member->teams as $team)<span title="{{ $team->name }}" class="grid h-7 w-7 place-items-center rounded-full border-2 border-white bg-violet-100 text-[10px] font-bold text-violet-700">{{ strtoupper(mb_substr($team->name, 0, 1)) }}</span>@empty<span class="text-xs text-slate-400">{{ __('No team') }}</span>@endforelse</div></div>
                                <span class="badge {{ $member->pivot->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ __(ucfirst($member->pivot->status)) }}</span>
                                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                            </summary>

                            <div class="border-t border-slate-100 bg-slate-50 p-5">
                                @if($member->pivot->role === 'owner')
                                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ __('The owner always has full access and cannot be deactivated, removed, or assigned a lower role.') }}</div>
                                @else
                                    <form method="POST" action="{{ route('companies.people.members.update', [$company, $member]) }}" x-data="{ accessMode: '{{ $usesRolePreset ? 'role' : 'custom' }}' }">
                                        @csrf @method('PUT')
                                        <div class="grid gap-4 md:grid-cols-3">
                                            <div><label class="label">{{ __('Role') }}</label><select class="field" name="role">@foreach($roles as $key => [$english, $arabic]) @continue($key === 'owner') <option value="{{ $key }}" @selected($member->pivot->role === $key)>{{ app()->isLocale('ar') ? $arabic : $english }}</option>@endforeach</select></div>
                                            <div><label class="label">{{ __('Status') }}</label><select class="field" name="status"><option value="active" @selected($member->pivot->status === 'active')>{{ __('Active') }}</option><option value="inactive" @selected($member->pivot->status === 'inactive')>{{ __('Inactive') }}</option></select></div>
                                            <div><label class="label">{{ __('Access source') }}</label><select class="field" name="access_mode" x-model="accessMode"><option value="role">{{ __('Use role preset') }}</option><option value="custom">{{ __('Custom access') }}</option></select></div>
                                        </div>
                                        @if($teams->isNotEmpty())
                                            <div class="mt-4"><span class="label">{{ __('Team membership') }}</span><div class="flex flex-wrap gap-2">@foreach($teams as $team)<label class="cursor-pointer"><input type="checkbox" name="team_ids[]" value="{{ $team->id }}" @checked($member->teams->contains('id', $team->id)) class="peer sr-only"><span class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 peer-checked:text-violet-800">{{ $team->name }}</span></label>@endforeach</div></div>
                                        @endif
                                        <div x-show="accessMode === 'custom'" class="mt-4 rounded-xl border border-slate-200 bg-white p-4">@include('people.partials.permissions', ['selectedPermissions' => $personalPermissions])</div>
                                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                                            <p class="text-xs text-slate-500">{{ __('Effective access: :granted of :total permissions', ['granted' => count($member->effective_permissions), 'total' => count($permissions)]) }}</p>
                                            <div class="flex gap-2"><button class="btn-primary">{{ __('Save access') }}</button></div>
                                        </div>
                                    </form>
                                    @if($member->id !== Auth::id())
                                        <form method="POST" action="{{ route('companies.people.members.destroy', [$company, $member]) }}" class="mt-3 border-t border-slate-200 pt-3" onsubmit='return confirm(@js(__('Remove this user from the company?')))'>@csrf @method('DELETE')<button class="text-sm font-semibold text-rose-600 hover:underline">{{ __('Remove from company') }}</button></form>
                                    @endif
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section x-show="tab === 'teams'" x-cloak class="space-y-6">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                <div class="space-y-4">
                    @forelse($teams as $team)
                        @php $colorClasses = ['emerald' => 'bg-emerald-100 text-emerald-800', 'blue' => 'bg-blue-100 text-blue-800', 'violet' => 'bg-violet-100 text-violet-800', 'amber' => 'bg-amber-100 text-amber-800', 'rose' => 'bg-rose-100 text-rose-800', 'slate' => 'bg-slate-200 text-slate-800']; @endphp
                        <details class="card group">
                            <summary class="flex cursor-pointer list-none items-center gap-4 p-5">
                                <span class="grid h-11 w-11 place-items-center rounded-xl font-bold {{ $colorClasses[$team->color] ?? $colorClasses['slate'] }}">{{ strtoupper(mb_substr($team->name, 0, 2)) }}</span>
                                <div class="min-w-0 flex-1"><h3 class="font-bold">{{ $team->name }}</h3><p class="truncate text-sm text-slate-500">{{ $team->description ?: __('No description') }}</p></div>
                                <div class="text-end"><strong class="block text-sm">{{ __(':count users', ['count' => $team->users->count()]) }}</strong><small class="text-slate-400">{{ __(':count permissions', ['count' => count($team->permissions ?? [])]) }}</small></div>
                                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                            </summary>
                            <form method="POST" action="{{ route('companies.people.teams.update', [$company, $team]) }}" class="border-t border-slate-200 bg-slate-50 p-5">@csrf @method('PUT')
                                <div class="grid gap-4 md:grid-cols-2"><div><label class="label">{{ __('Team name') }}</label><input class="field" name="name" value="{{ $team->name }}" required></div><div><label class="label">{{ __('Color') }}</label><select class="field" name="color">@foreach(['emerald','blue','violet','amber','rose','slate'] as $color)<option value="{{ $color }}" @selected($team->color === $color)>{{ __(ucfirst($color)) }}</option>@endforeach</select></div></div>
                                <div class="mt-4"><label class="label">{{ __('Description') }}</label><textarea class="field" name="description" rows="2">{{ $team->description }}</textarea></div>
                                <div class="mt-4"><span class="label">{{ __('Members') }}</span><div class="grid max-h-44 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-2">@foreach($members->where('pivot.status', 'active') as $member)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="member_ids[]" value="{{ $member->id }}" @checked($team->users->contains('id', $member->id)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><span class="truncate">{{ $member->name }}</span></label>@endforeach</div></div>
                                <div class="mt-4"><span class="label">{{ __('Team permissions') }}</span>@include('people.partials.permissions', ['selectedPermissions' => $team->permissions ?? []])</div>
                                <div class="mt-5"><button class="btn-primary">{{ __('Save team') }}</button></div>
                            </form>
                            <form method="POST" action="{{ route('companies.people.teams.destroy', [$company, $team]) }}" class="border-t border-slate-200 bg-slate-50 px-5 pb-5" onsubmit='return confirm(@js(__('Delete this team?')))'>@csrf @method('DELETE')<button class="text-sm font-semibold text-rose-600">{{ __('Delete team') }}</button></form>
                        </details>
                    @empty
                        <div class="card p-10 text-center"><div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-violet-100 text-2xl">♟</div><h3 class="mt-4 font-bold">{{ __('No teams yet') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Create a team to grant shared access to a group of users.') }}</p></div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('companies.people.teams.store', $company) }}" class="card h-fit p-5">@csrf
                    <div><h2 class="font-bold">{{ __('Create team') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Permissions are added to each team member’s access.') }}</p></div>
                    <div class="mt-5"><label class="label">{{ __('Team name') }}</label><input class="field" name="name" required placeholder="{{ __('e.g. Warehouse team') }}"></div>
                    <div class="mt-4"><label class="label">{{ __('Description') }}</label><textarea class="field" name="description" rows="2" placeholder="{{ __('What does this team handle?') }}"></textarea></div>
                    <div class="mt-4"><label class="label">{{ __('Color') }}</label><select class="field" name="color">@foreach(['emerald','blue','violet','amber','rose','slate'] as $color)<option value="{{ $color }}">{{ __(ucfirst($color)) }}</option>@endforeach</select></div>
                    @if($members->where('pivot.status', 'active')->isNotEmpty())<div class="mt-4"><span class="label">{{ __('Members') }}</span><div class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">@foreach($members->where('pivot.status', 'active') as $member)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="member_ids[]" value="{{ $member->id }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">{{ $member->name }}</label>@endforeach</div></div>@endif
                    <div class="mt-4"><span class="label">{{ __('Team permissions') }}</span>@include('people.partials.permissions', ['selectedPermissions' => []])</div>
                    <button class="btn-primary mt-5 w-full">{{ __('Create team') }}</button>
                </form>
            </div>
        </section>

        <section x-show="tab === 'access'" x-cloak class="space-y-6">
            <div class="card overflow-hidden">
                <div class="border-b border-slate-200 p-5"><h2 class="font-bold">{{ __('Role presets') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('A custom user selection replaces their role preset. Team permissions are then added on top.') }}</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-start">{{ __('Permission') }}</th>@foreach($roles as $key => [$english, $arabic])<th class="px-3 py-3 text-center">{{ app()->isLocale('ar') ? $arabic : $english }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@foreach($permissions as $key => [$label, $description, $arabic])<tr><td class="px-4 py-3"><strong class="block">{{ app()->isLocale('ar') ? $arabic : $label }}</strong><small class="text-slate-500">{{ __($description) }}</small></td>@foreach($roles as $role => $roleLabel)<td class="px-3 py-3 text-center">@if(in_array('*', $rolePermissions[$role], true) || in_array($key, $rolePermissions[$role], true))<span class="inline-grid h-6 w-6 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700">✓</span>@else<span class="text-slate-300">—</span>@endif</td>@endforeach</tr>@endforeach</tbody></table></div>
            </div>
            <div class="grid gap-4 md:grid-cols-3"><div class="card p-5"><span class="badge bg-blue-100 text-blue-700">1</span><h3 class="mt-3 font-bold">{{ __('Role preset') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Provides a sensible starting set for each job level.') }}</p></div><div class="card p-5"><span class="badge bg-violet-100 text-violet-700">2</span><h3 class="mt-3 font-bold">{{ __('Personal override') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('When enabled, the selected permissions replace the role preset.') }}</p></div><div class="card p-5"><span class="badge bg-emerald-100 text-emerald-700">3</span><h3 class="mt-3 font-bold">{{ __('Team access') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Permissions from every assigned team are added to the user.') }}</p></div></div>
        </section>
    </div>
</x-app-layout>
