<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CompanyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyPermission
{
    public function __construct(private CompanyAccess $access) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $company = $request->route('company');

        abort_unless(
            $company instanceof Company
            && $this->access->allows($request->user(), $company, $permission, $company->currentMembership ?? null),
            403,
            'Your role does not include this permission.'
        );

        return $next($request);
    }
}
