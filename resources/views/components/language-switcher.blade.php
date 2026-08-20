@props(['dark' => false, 'compact' => false])

<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['inline-flex rounded-xl p-1', 'bg-slate-900' => $dark, 'border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900' => ! $dark]) }} aria-label="{{ __('Language') }}">
    @csrf
    @foreach (['en' => 'English', 'ar' => 'العربية'] as $locale => $label)
        <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="rounded-lg px-{{ $compact ? '2' : '3' }} py-1.5 text-xs font-bold transition {{ app()->getLocale() === $locale ? ($dark ? 'bg-emerald-500 text-slate-950' : 'bg-white text-emerald-700 shadow-sm dark:bg-slate-700 dark:text-emerald-300') : ($dark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white') }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
            {{ $label }}
        </button>
    @endforeach
</form>
