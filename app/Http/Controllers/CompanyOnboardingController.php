<?php

namespace App\Http\Controllers;

use App\Mail\CompanyInvitationMail;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CompanyOnboardingController extends Controller
{
    private const CURRENCIES = [
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'KWD' => 'Kuwaiti Dinar',
        'BHD' => 'Bahraini Dinar',
        'QAR' => 'Qatari Riyal',
        'OMR' => 'Omani Rial',
    ];

    public function edit(Request $request, Company $company): View|RedirectResponse
    {
        if ($company->onboarding_completed_at) {
            return redirect()->route('companies.dashboard', $company);
        }

        $this->authorizeSetup($request, $company);
        $warehouse = $company->locations()->where('is_default', true)->firstOrFail();

        return view('companies.onboarding', [
            'company' => $company,
            'warehouse' => $warehouse,
            'currencies' => self::CURRENCIES,
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        if ($company->onboarding_completed_at) {
            return redirect()->route('companies.dashboard', $company);
        }

        $this->authorizeSetup($request, $company);
        $data = $request->validate([
            'currency' => ['required', Rule::in(array_keys(self::CURRENCIES))],
            'warehouse_name' => ['required', 'string', 'max:120'],
            'warehouse_address' => ['required', 'string', 'max:255'],
            'team_name' => ['nullable', 'string', 'max:100', Rule::unique('teams')->where('company_id', $company->id)],
            'invite_email' => ['nullable', 'email', 'max:255'],
            'invite_role' => ['required_with:invite_email', Rule::in(['admin', 'manager', 'staff', 'viewer'])],
        ]);

        if (! empty($data['invite_email'])) {
            $data['invite_email'] = Str::lower(trim($data['invite_email']));
            $existingUser = User::whereRaw('LOWER(email) = ?', [$data['invite_email']])->first();

            if ($existingUser && $company->users()->whereKey($existingUser->id)->exists()) {
                throw ValidationException::withMessages([
                    'invite_email' => __('This person is already a member of the company.'),
                ]);
            }
        }

        $invitation = null;

        DB::transaction(function () use ($company, $data, $request, &$invitation) {
            $team = ! empty($data['team_name'])
                ? $company->teams()->create([
                    'name' => $data['team_name'],
                    'description' => 'Created during company setup.',
                    'color' => 'violet',
                    'permissions' => [],
                ])
                : null;

            $company->locations()->where('is_default', true)->firstOrFail()->update([
                'name' => $data['warehouse_name'],
                'address' => $data['warehouse_address'],
            ]);

            $company->update([
                'currency' => $data['currency'],
                'onboarding_completed_at' => now(),
            ]);

            if (! empty($data['invite_email'])) {
                $invitation = $company->invitations()->create([
                    'team_id' => $team?->id,
                    'invited_by_user_id' => $request->user()->id,
                    'email' => $data['invite_email'],
                    'role' => $data['invite_role'],
                    'token' => Str::random(64),
                    'expires_at' => now()->addDays(7),
                ]);
            }
        });

        $message = __('Company setup completed. Welcome to your inventory workspace.');

        if ($invitation instanceof CompanyInvitation) {
            $invitation->load(['company', 'team', 'inviter']);

            try {
                Mail::to($invitation->email)->send(new CompanyInvitationMail($invitation));
                $message = __('Company setup completed and the invitation was sent.');
            } catch (Throwable $exception) {
                Log::warning('Company invitation email could not be sent.', [
                    'invitation_id' => $invitation->id,
                    'exception' => $exception->getMessage(),
                ]);
                $message = __('Company setup completed. The invitation was created but email delivery needs attention.');
            }
        }

        return redirect()->route('companies.dashboard', $company)
            ->with('success', $message);
    }

    private function authorizeSetup(Request $request, Company $company): void
    {
        $membership = $request->user()->companies()->whereKey($company->id)->firstOrFail();
        abort_unless(in_array($membership->pivot->role, ['owner', 'admin'], true), 403);
    }
}
