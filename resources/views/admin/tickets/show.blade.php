@extends('layouts.admin')
@section('title', $ticket->reference)
@section('heading', $ticket->subject)
@section('subheading', $ticket->reference.' · '.($ticket->company?->name ?? __('No company')))

@section('content')
    <div class="mb-5"><a href="{{ route('admin.tickets.index') }}" class="text-sm font-semibold text-cyan-700 hover:underline">{{ __('Back to ticket queue') }}</a></div>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-4">
            @foreach($ticket->messages as $message)
                <article class="card overflow-hidden {{ $message->is_admin ? 'border-cyan-200' : '' }}">
                    <header class="flex items-center justify-between gap-3 border-b px-5 py-3 {{ $message->is_admin ? 'border-cyan-100 bg-cyan-50' : 'border-slate-100 bg-slate-50' }}"><div class="flex items-center gap-2"><span class="grid h-8 w-8 place-items-center rounded-full {{ $message->is_admin ? 'bg-cyan-500 text-white' : 'bg-violet-100 text-violet-700' }} text-xs font-bold">{{ $message->is_admin ? 'SP' : strtoupper(mb_substr($message->user?->name ?? '?', 0, 1)) }}</span><strong class="text-sm">{{ $message->user?->name ?? __('Deleted user') }}</strong>@if($message->is_admin)<span class="badge bg-cyan-100 text-cyan-700">{{ __('Admin reply') }}</span>@else<span class="badge bg-violet-50 text-violet-700">{{ __('Customer') }}</span>@endif</div><time class="text-xs text-slate-400">{{ $message->created_at->format('M d, Y H:i') }}</time></header>
                    <div class="whitespace-pre-wrap break-words px-5 py-5 text-sm leading-7 text-slate-700">{{ $message->body }}</div>
                </article>
            @endforeach
            @if($ticket->status !== 'closed')
                <form method="POST" action="{{ route('admin.tickets.replies.store', $ticket) }}" class="card p-5">@csrf<label for="message" class="label">{{ __('Reply to customer') }}</label><textarea id="message" name="message" class="field min-h-40" maxlength="5000" required>{{ old('message') }}</textarea><p class="mt-2 text-xs text-slate-400">{{ __('Sending a reply changes the status to awaiting customer.') }}</p><div class="mt-4 flex justify-end"><button class="inline-flex items-center justify-center rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700">{{ __('Send reply') }}</button></div></form>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600">{{ __('Reopen the ticket before sending another reply.') }}</div>
            @endif
        </div>

        <aside class="space-y-5">
            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="card p-5">@csrf @method('PATCH')<h2 class="font-bold">{{ __('Manage ticket') }}</h2><div class="mt-5 grid gap-4"><div><label class="label" for="status">{{ __('Status') }}</label><select class="field" id="status" name="status">@foreach(\App\Models\SupportTicket::STATUSES as $option)<option value="{{ $option }}" @selected($ticket->status === $option)>{{ __(str($option)->replace('_', ' ')->title()->toString()) }}</option>@endforeach</select></div><div><label class="label" for="priority">{{ __('Priority') }}</label><select class="field" id="priority" name="priority">@foreach(\App\Models\SupportTicket::PRIORITIES as $option)<option value="{{ $option }}" @selected($ticket->priority === $option)>{{ __(ucfirst($option)) }}</option>@endforeach</select></div><div><label class="label" for="assigned_to">{{ __('Assigned to') }}</label><select class="field" id="assigned_to" name="assigned_to"><option value="">{{ __('Unassigned') }}</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected($ticket->assigned_to === $admin->id)>{{ $admin->name }}</option>@endforeach</select></div><button class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Save changes') }}</button></div></form>
            <div class="card p-5"><h2 class="font-bold">{{ __('Customer details') }}</h2><dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs text-slate-400">{{ __('User') }}</dt><dd class="mt-1 font-semibold">{{ $ticket->user->name }}</dd><dd class="text-slate-500">{{ $ticket->user->email }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Company') }}</dt><dd class="mt-1">{{ $ticket->company?->name ?? __('No company') }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Category') }}</dt><dd class="mt-1">{{ __(ucfirst($ticket->category)) }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Created') }}</dt><dd class="mt-1">{{ $ticket->created_at->format('M d, Y H:i') }}</dd></div></dl></div>
        </aside>
    </div>
@endsection
