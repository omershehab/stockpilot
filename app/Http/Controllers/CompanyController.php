<?php

namespace App\Http\Controllers;

use App\Actions\CreateCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request, CreateCompany $creator): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $company = $creator->handle($request->user(), $data['name'], onboardingComplete: false);

        return redirect()->route('companies.onboarding.edit', $company)
            ->with('success', __('Company workspace created. Complete the one-time setup below.'));
    }
}
