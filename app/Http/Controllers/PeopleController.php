<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use App\Support\CompanyAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function __construct(private CompanyAccess $access) {}

    public function index(Company $company): View
    {
        $members = $company->users()
            ->with(['teams' => fn ($query) => $query->where('teams.company_id', $company->id)])
            ->orderByRaw("CASE company_user.role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'manager' THEN 3 WHEN 'staff' THEN 4 ELSE 5 END")
            ->orderBy('users.name')
            ->get();

        $members->each(fn (User $member) => $member->setAttribute(
            'effective_permissions',
            $this->access->permissionsFor($member, $company, $member->pivot)
        ));

        $teams = $company->teams()->with(['users' => fn ($query) => $query->orderBy('name')])->orderBy('name')->get();

        return view('people.index', [
            'company' => $company,
            'members' => $members,
            'teams' => $teams,
            'permissionGroups' => $this->access->groupedPermissions(),
            'permissions' => CompanyAccess::PERMISSIONS,
            'roles' => CompanyAccess::ROLE_LABELS,
            'rolePermissions' => CompanyAccess::ROLE_PERMISSIONS,
        ]);
    }

    public function storeMember(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateMember($request, $company, creating: true);
        $email = Str::lower(trim($data['email']));
        $temporaryPassword = null;

        DB::transaction(function () use ($company, $data, $email, &$temporaryPassword) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);
            if ($lockedCompany->hasReachedUserLimit()) {
                throw ValidationException::withMessages([
                    'user_limit' => __('The user limit for this company has been reached. Contact the platform administrator to increase it.'),
                ]);
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                $temporaryPassword = $data['password'] ?? Str::password(14);
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $temporaryPassword,
                ]);
            }

            abort_if($company->users()->whereKey($user->id)->exists(), 422, __('This user is already a member of the company.'));

            $company->users()->attach($user->id, [
                'role' => $data['role'],
                'status' => $data['status'],
                'permissions' => $this->personalPermissions($data),
            ]);
            $user->teams()->syncWithoutDetaching($data['team_ids'] ?? []);
        });

        $response = back()->with('success', __('User added to the company successfully.'));

        if ($temporaryPassword) {
            $response->with('temporary_password', $temporaryPassword)->with('new_user_email', $email);
        }

        return $response;
    }

    public function updateMember(Request $request, Company $company, User $user): RedirectResponse
    {
        $membership = $this->membership($company, $user);
        abort_if($membership->role === 'owner', 403, __('The owner account cannot be changed here.'));

        $data = $this->validateMember($request, $company);
        if ($request->user()->is($user) && $data['status'] !== 'active') {
            return back()->withErrors(['status' => __('You cannot deactivate your own access.')]);
        }

        DB::transaction(function () use ($company, $user, $data) {
            $company->users()->updateExistingPivot($user->id, [
                'role' => $data['role'],
                'status' => $data['status'],
                'permissions' => $this->personalPermissions($data),
            ]);
            $this->syncCompanyTeams($user, $company, $data['team_ids'] ?? []);
        });

        return back()->with('success', __('User access updated.'));
    }

    public function destroyMember(Request $request, Company $company, User $user): RedirectResponse
    {
        $membership = $this->membership($company, $user);
        abort_if($membership->role === 'owner', 403, __('The company owner cannot be removed.'));

        if ($request->user()->is($user)) {
            return back()->withErrors(['member' => __('You cannot remove your own account.')]);
        }

        DB::transaction(function () use ($company, $user) {
            $this->syncCompanyTeams($user, $company, []);
            $company->users()->detach($user->id);
        });

        return back()->with('success', __('User removed from the company.'));
    }

    public function storeTeam(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateTeam($request, $company);
        $team = $company->teams()->create($data);
        $team->users()->sync($data['member_ids'] ?? []);

        return back()->with('success', __('Team created successfully.'));
    }

    public function updateTeam(Request $request, Company $company, Team $team): RedirectResponse
    {
        $this->ensureCompanyTeam($company, $team);
        $data = $this->validateTeam($request, $company, $team);

        DB::transaction(function () use ($team, $data) {
            $team->update($data);
            $team->users()->sync($data['member_ids'] ?? []);
        });

        return back()->with('success', __('Team updated successfully.'));
    }

    public function destroyTeam(Company $company, Team $team): RedirectResponse
    {
        $this->ensureCompanyTeam($company, $team);
        $team->delete();

        return back()->with('success', __('Team deleted. Users keep their individual access.'));
    }

    private function validateMember(Request $request, Company $company, bool $creating = false): array
    {
        $rules = [
            'role' => ['required', Rule::in(['admin', 'manager', 'staff', 'viewer'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'access_mode' => ['required', Rule::in(['role', 'custom'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(CompanyAccess::PERMISSIONS))],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => [Rule::exists('teams', 'id')->where('company_id', $company->id)],
        ];

        if ($creating) {
            $rules += [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['nullable', 'string', 'min:8', 'max:100'],
            ];
        }

        return $request->validate($rules);
    }

    private function validateTeam(Request $request, Company $company, ?Team $team = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('teams')->where('company_id', $company->id)->ignore($team)],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', Rule::in(['emerald', 'blue', 'violet', 'amber', 'rose', 'slate'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(CompanyAccess::PERMISSIONS))],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => [Rule::exists('company_user', 'user_id')->where('company_id', $company->id)->where('status', 'active')],
        ]);
    }

    private function personalPermissions(array $data): ?string
    {
        return $data['access_mode'] === 'custom'
            ? json_encode(array_values(array_unique($data['permissions'] ?? [])))
            : null;
    }

    private function membership(Company $company, User $user): mixed
    {
        $membership = $company->users()->whereKey($user->id)->first()?->pivot;
        abort_unless($membership, 404);

        return $membership;
    }

    private function syncCompanyTeams(User $user, Company $company, array $teamIds): void
    {
        $currentCompanyTeamIds = $user->teams()->where('teams.company_id', $company->id)->pluck('teams.id');
        $user->teams()->detach($currentCompanyTeamIds);
        $user->teams()->syncWithoutDetaching($teamIds);
    }

    private function ensureCompanyTeam(Company $company, Team $team): void
    {
        abort_unless($team->company_id === $company->id, 404);
    }
}
