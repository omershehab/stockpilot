<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');
        $plan = (string) $request->query('plan');

        $companies = Company::with(['users' => fn ($query) => $query->where('company_user.role', 'owner')])
            ->withCount(['users', 'products', 'locations', 'stockTransactions'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when($status, fn ($query) => $query->where('subscription_status', $status))
            ->when($plan, fn ($query) => $query->where('plan', $plan))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.subscribers.index', [
            'companies' => $companies,
            'search' => $search,
            'status' => $status,
            'plan' => $plan,
            'plans' => Company::distinct()->orderBy('plan')->pluck('plan'),
            'statuses' => Company::distinct()->orderBy('subscription_status')->pluck('subscription_status'),
        ]);
    }

    public function show(Company $company): View
    {
        $company->load([
            'users' => fn ($query) => $query->orderBy('name'),
            'locations' => fn ($query) => $query->orderBy('name'),
        ])->loadCount(['users', 'products', 'locations', 'stockTransactions', 'stockCounts']);

        $inventoryUnits = (float) $company->stocks()->sum('quantity');
        $inventoryValue = (float) $company->stocks()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->selectRaw('COALESCE(SUM(stocks.quantity * products.cost), 0) as aggregate')
            ->value('aggregate');
        $recentProducts = $company->products()->withSum('stocks', 'quantity')->latest()->limit(8)->get();
        $recentActivity = $company->stockTransactions()->with('user')->withCount('lines')->latest('occurred_at')->limit(10)->get();
        $monthlyTransactions = $company->stockTransactions()
            ->where('occurred_at', '>=', now()->subDays(30))->count();

        return view('admin.subscribers.show', compact(
            'company', 'inventoryUnits', 'inventoryValue', 'recentProducts',
            'recentActivity', 'monthlyTransactions'
        ));
    }

    public function updateLimits(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'product_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'user_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'warehouse_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        $company->update([
            'product_limit' => array_key_exists('product_limit', $data) ? $data['product_limit'] : $company->product_limit,
            'user_limit' => array_key_exists('user_limit', $data) ? $data['user_limit'] : $company->user_limit,
            'warehouse_limit' => array_key_exists('warehouse_limit', $data) ? $data['warehouse_limit'] : $company->warehouse_limit,
        ]);

        return back()->with('success', __('Company usage limits updated.'));
    }
}
