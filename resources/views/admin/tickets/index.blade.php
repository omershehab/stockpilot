@extends('layouts.admin')
@section('title', __('Support tickets'))
@section('heading', __('Support ticket queue'))
@section('subheading', __('Review inquiries, complaints, and suggestions from platform users'))

@section('content')
    <div class="card overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-slate-200 p-4 lg:grid-cols-[minmax(240px,1fr)_200px_200px_auto]">
            <input class="field" type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search reference, subject, user, or company…') }}">
            <select class="field" name="status"><option value="">{{ __('All statuses') }}</option>@foreach(\App\Models\SupportTicket::STATUSES as $option)<option value="{{ $option }}" @selected($status === $option)>{{ __(str($option)->replace('_', ' ')->title()->toString()) }}</option>@endforeach</select>
            <select class="field" name="category"><option value="">{{ __('All categories') }}</option>@foreach(\App\Models\SupportTicket::CATEGORIES as $option)<option value="{{ $option }}" @selected($category === $option)>{{ __(ucfirst($option)) }}</option>@endforeach</select>
            <div class="flex gap-2"><button class="btn-primary">{{ __('Filter') }}</button>@if($search || $status || $category)<a href="{{ route('admin.tickets.index') }}" class="btn-secondary">{{ __('Clear') }}</a>@endif</div>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('Ticket') }}</th><th class="px-5 py-3 text-start">{{ __('Customer') }}</th><th class="px-5 py-3 text-start">{{ __('Category') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th><th class="px-5 py-3 text-start">{{ __('Priority') }}</th><th class="px-5 py-3 text-start">{{ __('Assigned to') }}</th><th class="px-5 py-3 text-start">{{ __('Last activity') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4"><a href="{{ route('admin.tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-cyan-700">{{ $ticket->subject }}</a><small class="mt-1 block font-mono text-slate-400">{{ $ticket->reference }} · {{ trans_choice(':count message|:count messages', $ticket->messages_count, ['count' => $ticket->messages_count]) }}</small></td>
                        <td class="px-5 py-4"><strong class="block text-slate-700">{{ $ticket->user->name }}</strong><small class="block text-slate-400">{{ $ticket->company?->name ?? __('No company') }}</small></td>
                        <td class="px-5 py-4"><span class="badge bg-violet-50 text-violet-700">{{ __(ucfirst($ticket->category)) }}</span></td>
                        <td class="px-5 py-4"><span class="badge {{ match($ticket->status) { 'resolved' => 'bg-emerald-100 text-emerald-700', 'closed' => 'bg-slate-200 text-slate-600', 'awaiting_customer' => 'bg-amber-100 text-amber-700', 'in_progress' => 'bg-cyan-100 text-cyan-700', default => 'bg-blue-100 text-blue-700' } }}">{{ __(str($ticket->status)->replace('_', ' ')->title()->toString()) }}</span></td>
                        <td class="px-5 py-4"><span class="badge {{ match($ticket->priority) { 'urgent' => 'bg-rose-100 text-rose-700', 'high' => 'bg-orange-100 text-orange-700', 'low' => 'bg-slate-100 text-slate-500', default => 'bg-blue-50 text-blue-600' } }}">{{ __(ucfirst($ticket->priority)) }}</span></td>
                        <td class="px-5 py-4 text-slate-500">{{ $ticket->assignee?->name ?? __('Unassigned') }}</td>
                        <td class="px-5 py-4 text-slate-500"><span>{{ $ticket->last_message_at->format('M d, Y H:i') }}</span><small class="block text-slate-400">{{ $ticket->last_message_at->diffForHumans() }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-16 text-center"><strong class="block text-slate-600">{{ __('No matching tickets') }}</strong><span class="mt-1 block text-slate-400">{{ __('The support queue is clear.') }}</span></td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($tickets->hasPages())<div class="border-t border-slate-200 p-4">{{ $tickets->links() }}</div>@endif
    </div>
@endsection
