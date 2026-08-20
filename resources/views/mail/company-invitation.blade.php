<x-mail::message>
# {{ __('Join :company', ['company' => $invitation->company->name]) }}

{{ __(':name invited you to join :company on StockPilot as :role.', ['name' => $invitation->inviter?->name ?? __('A company administrator'), 'company' => $invitation->company->name, 'role' => __(ucfirst($invitation->role))]) }}

@if ($invitation->team)
{{ __('You will also join the :team team.', ['team' => $invitation->team->name]) }}
@endif

<x-mail::button :url="route('invitations.show', $invitation)">
{{ __('Accept invitation') }}
</x-mail::button>

{{ __('This secure invitation expires :time.', ['time' => $invitation->expires_at->diffForHumans()]) }}

{{ __('Thanks,') }}<br>
StockPilot
</x-mail::message>
