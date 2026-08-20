<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $companyId = $request->integer('company');
        $state = (string) $request->query('state');

        $products = Product::with(['company', 'category', 'barcodes', 'batches'])
            ->withSum('stocks', 'quantity')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhereHas('barcodes', fn ($query) => $query->where('code', 'like', "%{$search}%"))))
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($state === 'low', fn ($query) => $query->whereRaw('(SELECT COALESCE(SUM(stocks.quantity), 0) FROM stocks WHERE stocks.product_id = products.id) <= products.minimum_stock'))
            ->when($state === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($state === 'expiring', fn ($query) => $query->whereHas('batches', fn ($query) => $query->whereBetween('expiry_date', [today(), today()->addDays(30)])))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'companyId' => $companyId,
            'state' => $state,
        ]);
    }
}
