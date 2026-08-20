@extends('layouts.admin')
@section('title', __('Users'))
@section('heading', __('User monitor'))
@section('subheading', __('Review accounts, company memberships, roles, and team adoption'))

@section('content')
    <div class="card overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[minmax(260px,1fr)_280px_auto]">
            <input class="field" type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search name or email…') }}">
            <select class="field" name="company"><option value="">{{ __('All subscribers') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected($companyId === $company->id)>{{ $company->name }}</option>@endforeach</select>
            <div class="flex gap-2"><button class="btn-primary">{{ __('Filter') }}</button>@if($search || $companyId)<a href="{{ route('admin.users.index') }}" class="btn-secondary">{{ __('Clear') }}</a>@endif</div>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-[900px] text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('User') }}</th><th class="px-5 py-3 text-start">{{ __('Companies & roles') }}</th><th class="px-5 py-3 text-center">{{ __('Workspaces') }}</th><th class="px-5 py-3 text-center">{{ __('Teams') }}</th><th class="px-5 py-3 text-start">{{ __('Account type') }}</th><th class="px-5 py-3 text-start">{{ __('Joined') }}</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
                <tr class="hover:bg-slate-50"><td class="px-5 py-4"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-violet-100 font-bold text-violet-700">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><strong class="block">{{ $user->name }}</strong><small class="block text-slate-400">{{ $user->email }}</small>@if($user->mobile_number)<small class="mt-0.5 block font-mono text-slate-500" dir="ltr">{{ $user->mobile_number }}</small>@endif</div></div></td><td class="px-5 py-4"><div class="flex max-w-md flex-wrap gap-1.5">@forelse($user->companies->take(4) as $company)<a href="{{ route('admin.subscribers.show', $company) }}" class="badge bg-slate-100 text-slate-700 hover:bg-cyan-100 hover:text-cyan-700">{{ $company->name }} · {{ __(ucfirst($company->pivot->role)) }}</a>@empty<span class="text-slate-400">{{ __('No company membership') }}</span>@endforelse @if($user->companies->count() > 4)<span class="badge bg-slate-100 text-slate-500">+{{ $user->companies->count() - 4 }}</span>@endif</div></td><td class="px-5 py-4 text-center font-semibold">{{ $user->companies_count }}</td><td class="px-5 py-4 text-center">{{ $user->teams_count }}</td><td class="px-5 py-4">@if($user->is_platform_admin)<span class="badge bg-cyan-100 text-cyan-700">{{ __('Platform admin') }}</span>@else<span class="badge bg-slate-100 text-slate-600">{{ __('Subscriber user') }}</span>@endif</td><td class="px-5 py-4"><span class="text-slate-600">{{ $user->created_at->format('M d, Y') }}</span><small class="block text-slate-400">{{ $user->created_at->diffForHumans() }}</small></td></tr>
            @empty<tr><td colspan="6" class="px-5 py-16 text-center"><strong class="block text-slate-600">{{ __('No matching users') }}</strong><span class="mt-1 block text-sm text-slate-400">{{ __('Try changing your search.') }}</span></td></tr>@endforelse
        </tbody></table></div>
        @if($users->hasPages())<div class="border-t border-slate-200 p-4">{{ $users->links() }}</div>@endif
    </div>
@endsection
