<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Support\MobileNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyInvitationController extends Controller
{
    public function show(Request $request, CompanyInvitation $invitation): View
    {
        $this->ensureAcceptable($invitation);
        $invitation->load(['company', 'team', 'inviter']);

        return view('invitations.show', [
            'invitation' => $invitation,
            'existingAccount' => User::whereRaw('LOWER(email) = ?', [Str::lower($invitation->email)])->exists(),
            'emailMatches' => $request->user()
                ? Str::lower($request->user()->email) === Str::lower($invitation->email)
                : false,
        ]);
    }

    public function login(CompanyInvitation $invitation): RedirectResponse
    {
        $this->ensureAcceptable($invitation);
        session()->put('url.intended', route('invitations.show', $invitation));

        return redirect()->route('login');
    }

    public function accept(Request $request, CompanyInvitation $invitation): RedirectResponse
    {
        $this->ensureAcceptable($invitation);
        $email = Str::lower($invitation->email);
        $user = $request->user();
        $registrationData = null;

        if ($user) {
            abort_unless(Str::lower($user->email) === $email, 403, __('This invitation belongs to another email address.'));
        } else {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                return back()->withErrors(['email' => __('An account already exists for this email. Sign in to accept the invitation.')]);
            }

            $request->merge(['mobile_number' => MobileNumber::normalize($request->input('mobile_number'))]);
            $registrationData = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'mobile_number' => [...MobileNumber::rules(), Rule::unique(User::class, 'mobile_number')],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        }

        [$user, $registered, $company] = DB::transaction(function () use ($invitation, $user, $registrationData, $email) {
            $lockedInvitation = CompanyInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $this->ensureAcceptable($lockedInvitation);
            $company = Company::query()->lockForUpdate()->findOrFail($lockedInvitation->company_id);
            $isMember = $user && $company->users()->whereKey($user->id)->exists();

            if (! $isMember && $company->hasReachedUserLimit()) {
                throw ValidationException::withMessages([
                    'user_limit' => __('The user limit for this company has been reached. Contact the platform administrator to increase it.'),
                ]);
            }

            $registered = $user === null;
            if ($registered) {
                $user = User::create([
                    'name' => $registrationData['name'],
                    'email' => $email,
                    'mobile_number' => $registrationData['mobile_number'],
                    'preferred_locale' => app()->getLocale(),
                    'password' => $registrationData['password'],
                ]);
            }

            if (! $isMember) {
                $company->users()->attach($user->id, [
                    'role' => $lockedInvitation->role,
                    'status' => 'active',
                ]);
            }

            $team = $lockedInvitation->team;
            if ($team && $team->company_id === $company->id) {
                $team->users()->syncWithoutDetaching([$user->id]);
            }

            $lockedInvitation->update(['accepted_at' => now()]);

            return [$user, $registered, $company];
        });

        if ($registered) {
            event(new Registered($user));
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('companies.dashboard', $company)
            ->with('success', __('Invitation accepted. Welcome to :company.', ['company' => $company->name]));
    }

    private function ensureAcceptable(CompanyInvitation $invitation): void
    {
        abort_unless($invitation->isAcceptable(), 410, __('This invitation has expired or was already accepted.'));
    }
}
