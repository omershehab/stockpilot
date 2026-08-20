<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button type="button" @click="open = !open" class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900" aria-label="{{ __('Notifications') }}" :aria-expanded="open">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.9 18a3 3 0 0 1-5.8 0M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/></svg>
        @if($unreadCount > 0)<span class="absolute -end-1.5 -top-1.5 grid min-h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right class="absolute end-0 top-12 z-50 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div><h2 class="text-sm font-bold text-slate-900">{{ __('Notifications') }}</h2><p class="text-xs text-slate-400">{{ trans_choice(':count unread notification|:count unread notifications', $unreadCount, ['count' => $unreadCount]) }}</p></div>
            @if($unreadCount > 0)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="text-xs font-semibold text-emerald-700 hover:underline">{{ __('Mark all read') }}</button></form>@endif
        </div>
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf
                    <button class="flex w-full gap-3 border-b border-slate-100 px-4 py-3 text-start transition hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-emerald-50/60' }}">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700' }}">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </span>
                        @php
                            $messageParameters = $notification->data['message_parameters'] ?? [];
                            if (isset($messageParameters['status'])) $messageParameters['status'] = __($messageParameters['status']);
                        @endphp
                        <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-slate-800">{{ __($notification->data['title'] ?? 'Notification') }}</strong><span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ __($notification->data['message'] ?? '', $messageParameters) }}</span><small class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</small></span>
                        @if(! $notification->read_at)<span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>@endif
                    </button>
                </form>
            @empty
                <div class="px-6 py-10 text-center"><span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-400">✓</span><strong class="mt-3 block text-sm text-slate-600">{{ __('You are all caught up') }}</strong><span class="mt-1 block text-xs text-slate-400">{{ __('New ticket activity will appear here.') }}</span></div>
            @endforelse
        </div>
    </div>
</div>
