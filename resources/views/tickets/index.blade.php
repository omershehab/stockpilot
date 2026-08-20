<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold">{{ __('Support tickets') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Contact the platform team and track every response.') }}</p></div>
            <a href="{{ route('companies.tickets.create', $company) }}" class="btn-primary">+ {{ __('New ticket') }}</a>
        </div>
    </x-slot>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Ticket') }}</th><th class="px-5 py-3 text-start">{{ __('Category') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th><th class="px-5 py-3 text-center">{{ __('Messages') }}</th><th class="px-5 py-3 text-start">{{ __('Last activity') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4"><a href="{{ route('companies.tickets.show', [$company, $ticket]) }}" class="font-semibold text-slate-900 hover:text-emerald-700">{{ $ticket->subject }}</a><small class="mt-1 block font-mono text-slate-400">{{ $ticket->reference }}</small></td>
                        <td class="px-5 py-4"><span class="badge bg-violet-50 text-violet-700">{{ __(ucfirst($ticket->category)) }}</span></td>
                        <td class="px-5 py-4"><span class="badge {{ match($ticket->status) { 'resolved' => 'bg-emerald-100 text-emerald-700', 'closed' => 'bg-slate-200 text-slate-600', 'awaiting_customer' => 'bg-amber-100 text-amber-700', 'in_progress' => 'bg-cyan-100 text-cyan-700', default => 'bg-blue-100 text-blue-700' } }}">{{ __(str($ticket->status)->replace('_', ' ')->title()->toString()) }}</span></td>
                        <td class="px-5 py-4 text-center font-semibold">{{ $ticket->messages_count }}</td>
                        <td class="px-5 py-4 text-slate-500"><span>{{ $ticket->last_message_at->format('M d, Y H:i') }}</span><small class="block text-slate-400">{{ $ticket->last_message_at->diffForHumans() }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-16 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-xl text-emerald-700">?</span><strong class="mt-3 block text-slate-700">{{ __('No support tickets yet') }}</strong><p class="mt-1 text-slate-400">{{ __('Create a ticket whenever you need help or want to share feedback.') }}</p><a href="{{ route('companies.tickets.create', $company) }}" class="mt-4 inline-flex font-semibold text-emerald-700 hover:underline">{{ __('Create your first ticket') }}</a></td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($tickets->hasPages())<div class="border-t border-slate-200 p-4">{{ $tickets->links() }}</div>@endif
    </div>
</x-app-layout>
