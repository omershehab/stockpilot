<x-guest-layout>
    <div class="text-center">
        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">{{ __('Company invitation') }}</span>
        <h1 class="mt-4 text-2xl font-bold">{{ __('Join :company', ['company' => $invitation->company->name]) }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __(':name invited you as :role.', ['name' => $invitation->inviter?->name ?? __('A company administrator'), 'role' => __(ucfirst($invitation->role))]) }}</p>
        @if ($invitation->team)
            <p class="mt-2 text-sm font-semibold text-violet-700">{{ __('Team: :team', ['team' => $invitation->team->name]) }}</p>
        @endif
    </div>

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    @auth
        @if ($emailMatches)
            <form method="POST" action="{{ route('invitations.accept', $invitation) }}" class="mt-6">
                @csrf
                <button class="btn-primary w-full py-3">{{ __('Accept invitation') }}</button>
            </form>
        @else
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                {{ __('This invitation is for :email. Sign out and use the matching account.', ['email' => $invitation->email]) }}
            </div>
        @endif
    @else
        @if ($existingAccount)
            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{{ __('An account already exists for :email.', ['email' => $invitation->email]) }}</div>
            <a href="{{ route('invitations.login', $invitation) }}" class="btn-primary mt-4 block w-full py-3 text-center">{{ __('Sign in to accept') }}</a>
        @else
            <form method="POST" action="{{ route('invitations.accept', $invitation) }}" class="mt-6 space-y-4">
                @csrf
                <div><label class="label" for="name">{{ __('Your name') }}</label><input class="field" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"></div>
                <div><label class="label" for="email">{{ __('Email') }}</label><input class="field bg-slate-50" id="email" value="{{ $invitation->email }}" disabled></div>
                <div><label class="label" for="mobile_number">{{ __('Mobile number') }}</label><input class="field" id="mobile_number" name="mobile_number" type="tel" value="{{ old('mobile_number') }}" required autocomplete="tel" inputmode="tel" placeholder="+966 5X XXX XXXX" dir="ltr"><p class="mt-1 text-xs text-slate-500">{{ __('Include country code, e.g. +966 5X XXX XXXX') }}</p></div>
                <div><label class="label" for="password">{{ __('Password') }}</label><input class="field" id="password" name="password" type="password" required autocomplete="new-password"></div>
                <div><label class="label" for="password_confirmation">{{ __('Confirm Password') }}</label><input class="field" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
                <button class="btn-primary w-full py-3">{{ __('Create account & join') }}</button>
            </form>
        @endif
    @endauth

    <p class="mt-5 text-center text-xs text-slate-400">{{ __('Invitation expires :time.', ['time' => $invitation->expires_at->diffForHumans()]) }}</p>
</x-guest-layout>
