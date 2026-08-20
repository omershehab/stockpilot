<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(['en', 'ar'])],
        ]);

        $request->session()->put('locale', $data['locale']);
        $request->user()?->update(['preferred_locale' => $data['locale']]);

        return back();
    }
}
