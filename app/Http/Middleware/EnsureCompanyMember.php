<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyAccess;
use App\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyMember
{
    public function __construct(private CurrentCompany $currentCompany, private CompanyAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->route('company');

        abort_unless($company instanceof Company && $company->is_active, 404);

        $membership = $request->user()->companies()
            ->whereKey($company->getKey())
            ->wherePivot('status', 'active')
            ->first();

        abort_unless($membership, 403, __('You do not have access to this company.'));

        $company->setRelation('currentMembership', $membership->pivot);
        $this->currentCompany->set($company);
        View::share('currentCompany', $company);
        View::share('currentMembership', $membership->pivot);
        View::share('currentPermissions', $this->access->permissionsFor($request->user(), $company, $membership->pivot));

        if (! $company->onboarding_completed_at && ! $request->routeIs('companies.onboarding.*')) {
            return redirect()->route('companies.onboarding.edit', $company);
        }

        return $next($request);
    }
}
