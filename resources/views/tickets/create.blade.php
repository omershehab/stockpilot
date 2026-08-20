<x-app-layout>
    <x-slot name="header"><div><h1 class="text-2xl font-bold">{{ __('New support ticket') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Tell the platform team how we can help.') }}</p></div></x-slot>
    <div class="mx-auto max-w-3xl">
        <form method="POST" action="{{ route('companies.tickets.store', $company) }}" class="card p-6 sm:p-8">
            @csrf
            <div class="grid gap-6">
                <div><label for="category" class="label">{{ __('Request type') }}</label><select id="category" name="category" class="field" required><option value="">{{ __('Choose a request type') }}</option><option value="general" @selected(old('category') === 'general')>{{ __('General inquiry') }}</option><option value="complaint" @selected(old('category') === 'complaint')>{{ __('Complaint') }}</option><option value="suggestion" @selected(old('category') === 'suggestion')>{{ __('Suggestion') }}</option></select></div>
                <div><label for="subject" class="label">{{ __('Subject') }}</label><input id="subject" name="subject" class="field" value="{{ old('subject') }}" maxlength="160" required placeholder="{{ __('Briefly describe your request') }}"></div>
                <div><label for="message" class="label">{{ __('Message') }}</label><textarea id="message" name="message" class="field min-h-48" maxlength="5000" required placeholder="{{ __('Include any details that will help us respond.') }}">{{ old('message') }}</textarea><p class="mt-2 text-xs text-slate-400">{{ __('Up to 5,000 characters. Do not include passwords or payment details.') }}</p></div>
                <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('companies.tickets.index', $company) }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Submit ticket') }}</button></div>
            </div>
        </form>
    </div>
</x-app-layout>
