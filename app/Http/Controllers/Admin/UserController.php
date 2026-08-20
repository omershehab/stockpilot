<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $companyId = $request->integer('company');

        $users = User::with(['companies' => fn ($query) => $query->orderBy('name')])
            ->withCount(['companies', 'teams'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($companyId, fn ($query) => $query->whereHas('companies', fn ($query) => $query->whereKey($companyId)))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'companyId' => $companyId,
        ]);
    }
}
