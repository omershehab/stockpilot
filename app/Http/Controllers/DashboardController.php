<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Company $company): View
    {
        $products = $company->products()->withSum('stocks', 'quantity')->get();
        $totalUnits = (float) $company->stocks()->sum('quantity');
        $inventoryValue = (float) $company->stocks()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->selectRaw('COALESCE(SUM(stocks.quantity * products.cost), 0) as aggregate')
            ->value('aggregate');
        $lowStockCount = $products->filter(fn (Product $product) => (float) ($product->stocks_sum_quantity ?? 0) <= (float) $product->minimum_stock)->count();
        $lowStockProducts = $products
            ->filter(fn (Product $product) => (float) ($product->stocks_sum_quantity ?? 0) <= (float) $product->minimum_stock)
            ->sortBy(fn (Product $product) => (float) ($product->stocks_sum_quantity ?? 0) - (float) $product->minimum_stock)
            ->take(5)
            ->values();

        $expiringBatches = ProductBatch::query()
            ->where('company_id', $company->id)
            ->whereBetween('expiry_date', [today(), today()->addDays(30)])
            ->with('product')
            ->orderBy('expiry_date')
            ->limit(5)
            ->get();
        $expiredBatchesCount = ProductBatch::query()
            ->where('company_id', $company->id)
            ->whereDate('expiry_date', '<', today())
            ->count();
        $openStockCounts = $company->stockCounts()->where('status', '!=', 'completed')->count();

        $warehouses = $company->locations()
            ->where('is_active', true)
            ->withCount('stocks')
            ->withSum('stocks', 'quantity')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
        $maxWarehouseUnits = max(1, (float) $warehouses->max('stocks_sum_quantity'));

        $recentTransactions = $company->stockTransactions()
            ->with(['user', 'lines.product', 'lines.location'])
            ->latest('occurred_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'company', 'products', 'totalUnits', 'inventoryValue', 'lowStockCount',
            'lowStockProducts', 'expiringBatches', 'expiredBatchesCount', 'openStockCounts',
            'warehouses', 'maxWarehouseUnits', 'recentTransactions'
        ));
    }
}
