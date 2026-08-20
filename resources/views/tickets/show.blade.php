<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><a href="{{ route('companies.tickets.index', $company) }}" class="text-sm font-medium text-emerald-700 hover:underline">{{ __('Support tickets') }}</a><h1 class="mt-1 text-2xl font-bold">{{ $ticket->subject }}</h1><p class="mt-1 font-mono text-xs text-slate-400">{{ $ticket->reference }}</p></div><span class="badge {{ match($ticket->status) { 'resolved' => 'bg-emerald-100 text-emerald-700', 'closed' => 'bg-slate-200 text-slate-600', 'awaiting_customer' => 'bg-amber-100 text-amber-700', 'in_progress' => 'bg-cyan-100 text-cyan-700', default => 'bg-blue-100 text-blue-700' } }}">{{ __(str($ticket->status)->replace('_', ' ')->title()->toString()) }}</span></div>
    </x-slot>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="space-y-4">
            @foreach($ticket->messages as $message)
                <article class="card overflow-hidden {{ $message->is_admin ? 'border-cyan-200' : '' }}">
                    <header class="flex items-center justify-between gap-3 border-b px-5 py-3 {{ $message->is_admin ? 'border-cyan-100 bg-cyan-50' : 'border-slate-100 bg-slate-50' }}"><div class="flex items-center gap-2"><span class="grid h-8 w-8 place-items-center rounded-full {{ $message->is_admin ? 'bg-cyan-500 text-white' : 'bg-emerald-100 text-emerald-700' }} text-xs font-bold">{{ $message->is_admin ? 'SP' : strtoupper(mb_substr($message->user?->name ?? '?', 0, 1)) }}</span><strong class="text-sm">{{ $message->is_admin ? __('Platform support') : ($message->user?->name ?? __('Deleted user')) }}</strong>@if($message->is_admin)<span class="badge bg-cyan-100 text-cyan-700">{{ __('Admin') }}</span>@endif</div><time class="text-xs text-slate-400">{{ $message->created_at->format('M d, Y H:i') }}</time></header>
                    <div class="whitespace-pre-wrap break-words px-5 py-5 text-sm leading-7 text-slate-700">{{ $message->body }}</div>
                </article>
            @endforeach
            @if($ticket->status !== 'closed')
                <form method="POST" action="{{ route('companies.tickets.replies.store', [$company, $ticket]) }}" class="card p-5">@csrf<label for="message" class="label">{{ __('Add a reply') }}</label><textarea id="message" name="message" class="field min-h-32" maxlength="5000" required>{{ old('message') }}</textarea><div class="mt-4 flex justify-end"><button class="btn-primary">{{ __('Send reply') }}</button></div></form>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600">{{ __('This ticket is closed. Create a new ticket if you need more help.') }}</div>
            @endif
        </div>
        <aside class="card h-fit p-5"><h2 class="font-bold">{{ __('Ticket details') }}</h2><dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs text-slate-400">{{ __('Category') }}</dt><dd class="mt-1 font-semibold">{{ __(ucfirst($ticket->category)) }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Created') }}</dt><dd class="mt-1">{{ $ticket->created_at->format('M d, Y H:i') }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Last activity') }}</dt><dd class="mt-1">{{ $ticket->last_message_at->diffForHumans() }}</dd></div><div><dt class="text-xs text-slate-400">{{ __('Assigned to') }}</dt><dd class="mt-1">{{ $ticket->assignee?->name ?? __('Support queue') }}</dd></div></dl></aside>
    </div>
</x-app-layout>
