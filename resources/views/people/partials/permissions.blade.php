<div class="grid gap-2 sm:grid-cols-2">
    @foreach($permissions as $permissionKey => [$permissionLabel, $permissionDescription, $permissionArabic])
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-emerald-300">
            <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" @checked(in_array($permissionKey, $selectedPermissions, true)) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span><strong class="block text-sm">{{ app()->isLocale('ar') ? $permissionArabic : $permissionLabel }}</strong><small class="mt-0.5 block leading-5 text-slate-500">{{ __($permissionDescription) }}</small></span>
        </label>
    @endforeach
</div>
