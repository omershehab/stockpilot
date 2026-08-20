<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">{{ __('Create a company') }}</h1><p class="mt-1 text-sm text-slate-500">{{ __('Start a separate tenant workspace for another business.') }}</p></x-slot>
    <div class="mx-auto max-w-xl card p-6">
        <form method="POST" action="{{ route('companies.store') }}" class="space-y-5">@csrf
            <div><label class="label" for="name">{{ __('Company name') }}</label><input class="field" id="name" name="name" value="{{ old('name') }}" required autofocus></div>
            <button class="btn-primary w-full">{{ __('Create company') }}</button>
        </form>
    </div>
</x-app-layout>
