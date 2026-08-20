<?php

namespace App\Http\Controllers\Auth;

use App\Actions\CreateCompany;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MobileNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, CreateCompany $creator): RedirectResponse
    {
        $request->merge(['mobile_number' => MobileNumber::normalize($request->input('mobile_number'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'mobile_number' => [...MobileNumber::rules(), Rule::unique(User::class, 'mobile_number')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'preferred_locale' => $request->session()->get('locale', 'en'),
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $companyName = (string) $request->string('company_name')->trim();
        $company = $creator->handle($user, $companyName ?: $user->name.' Company', onboardingComplete: false);

        return redirect(route('companies.onboarding.edit', $company, absolute: false));
    }
}
