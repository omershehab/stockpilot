<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companies = Company::query();
        $totalCompanies = (clone $companies)->count();
        $activeCompanies = (clone $companies)->where('is_active', true)->count();
        $trialCompanies = (clone $companies)->where('subscription_status', 'trial')->count();
        $expiringTrials = (clone $companies)
            ->where('subscription_status', 'trial')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        $metrics = [
            'companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'trial_companies' => $trialCompanies,
            'expiring_trials' => $expiringTrials,
            'users' => User::count(),
            'products' => Product::count(),
            'transactions_30d' => StockTransaction::where('occurred_at', '>=', now()->subDays(30))->count(),
            'inventory_units' => (float) DB::table('stocks')->sum('quantity'),
            'inventory_value' => (float) DB::table('stocks')
                ->join('products', 'products.id', '=', 'stocks.product_id')
                ->selectRaw('COALESCE(SUM(stocks.quantity * products.cost), 0) as aggregate')
                ->value('aggregate'),
            'open_tickets' => SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgent_tickets' => SupportTicket::whereNotIn('status', ['resolved', 'closed'])
                ->where('priority', 'urgent')->count(),
            'disabled_companies' => Company::where('is_active', false)->count(),
            'subscription_issues' => Company::whereIn('subscription_status', ['past_due', 'cancelled', 'canceled'])->count(),
        ];

        $planMix = Company::select('plan', DB::raw('COUNT(*) as total'))
            ->groupBy('plan')->orderByDesc('total')->get();
        $statusMix = Company::select('subscription_status', DB::raw('COUNT(*) as total'))
            ->groupBy('subscription_status')->orderByDesc('total')->get();

        $weeklySignups = collect(range(7, 0))->map(function (int $weeksAgo) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('M d'),
                'total' => Company::whereBetween('created_at', [$start, $end])->count(),
            ];
        });

        $recentCompanies = Company::with(['users' => fn ($query) => $query->where('company_user.role', 'owner')])
            ->withCount(['users', 'products'])
            ->latest()->limit(6)->get();

        $topCompanies = Company::withCount(['users', 'products', 'stockTransactions'])
            ->orderByDesc('products_count')->limit(6)->get();

        $quotaWatchlist = Company::withCount(['users', 'products', 'locations'])
            ->get()
            ->map(function (Company $company) {
                $resources = collect([
                    ['label' => 'Products', 'used' => $company->products_count, 'limit' => $company->product_limit],
                    ['label' => 'Users', 'used' => $company->users_count, 'limit' => $company->user_limit],
                    ['label' => 'Warehouses', 'used' => $company->locations_count, 'limit' => $company->warehouse_limit],
                ])->filter(fn (array $resource) => $resource['limit'] !== null)
                    ->map(fn (array $resource) => [
                        ...$resource,
                        'percent' => $resource['limit'] > 0
                            ? (int) round(($resource['used'] / $resource['limit']) * 100)
                            : 0,
                    ]);

                $highest = $resources->sortByDesc('percent')->first();
                $company->setAttribute('quota_resource', $highest['label'] ?? 'Products');
                $company->setAttribute('quota_used', $highest['used'] ?? 0);
                $company->setAttribute('quota_limit', $highest['limit'] ?? null);
                $company->setAttribute('quota_percent', $highest['percent'] ?? 0);

                return $company;
            })
            ->filter(fn (Company $company) => $company->quota_percent >= 80)
            ->sortByDesc('quota_percent')
            ->take(6)
            ->values();

        $recentActivity = StockTransaction::with(['company', 'user'])
            ->withCount('lines')->latest('occurred_at')->limit(8)->get();

        return view('admin.dashboard', compact(
            'metrics', 'planMix', 'statusMix', 'weeklySignups', 'recentCompanies',
            'topCompanies', 'recentActivity', 'totalCompanies', 'quotaWatchlist'
        ));
    }
}
