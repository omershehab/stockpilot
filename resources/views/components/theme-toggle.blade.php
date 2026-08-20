@props(['compact' => false])

<div x-data="{
    dark: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        document.documentElement.style.colorScheme = this.dark ? 'dark' : 'light';
        localStorage.setItem('stockpilot-theme', this.dark ? 'dark' : 'light');
    }
}" class="shrink-0">
    <button type="button" @click="toggleTheme()" class="grid {{ $compact ? 'h-9 w-9 rounded-lg' : 'h-10 w-10 rounded-xl' }} place-items-center border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-amber-300 dark:hover:border-slate-600 dark:hover:bg-slate-800" aria-label="{{ __('Toggle color theme') }}" title="{{ __('Toggle color theme') }}">
        <svg x-show="!dark" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42m0-12.72-1.42 1.42M7.06 16.94l-1.42 1.42M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
        <svg x-show="dark" x-cloak aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.4 15.3A8.5 8.5 0 0 1 8.7 3.6 8.5 8.5 0 1 0 20.4 15.3Z"/></svg>
        <span class="sr-only" x-show="!dark">{{ __('Switch to dark mode') }}</span>
        <span class="sr-only" x-show="dark" x-cloak>{{ __('Switch to light mode') }}</span>
    </button>
</div>
