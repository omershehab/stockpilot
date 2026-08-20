<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        $search = trim((string) $request->query('search'));
        $categoryId = $request->integer('category');
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '';
        $stock = in_array($request->query('stock'), ['in_stock', 'low', 'out'], true) ? $request->query('stock') : '';
        $expiry = in_array($request->query('expiry'), ['soon', 'expired', 'none'], true) ? $request->query('expiry') : '';
        $sort = in_array($request->query('sort'), ['latest', 'name', 'stock_low', 'stock_high', 'price_low', 'price_high'], true)
            ? $request->query('sort')
            : 'latest';
        $stockTotalSql = '(select COALESCE(SUM(stocks.quantity), 0) from stocks where stocks.product_id = products.id)';

        $productCount = $company->products()->count();
        $activeCount = $company->products()->where('is_active', true)->count();
        $lowStockCount = $company->products()->whereRaw("{$stockTotalSql} <= products.minimum_stock")->count();
        $outOfStockCount = $company->products()->whereRaw("{$stockTotalSql} <= 0")->count();
        $expiringCount = $company->products()->whereHas('batches', fn ($query) => $query
            ->whereBetween('expiry_date', [today(), today()->addDays(30)]))->count();

        $products = $company->products()
            ->with([
                'barcodes',
                'category',
                'batches' => fn ($query) => $query
                    ->orderByRaw('case when expiry_date is null then 1 else 0 end')
                    ->orderBy('expiry_date')
                    ->orderBy('id'),
            ])
            ->withSum('stocks', 'quantity')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('barcodes', fn ($barcodes) => $barcodes->where('code', 'like', "%{$search}%"));
            }))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($stock === 'in_stock', fn ($query) => $query->whereRaw("{$stockTotalSql} > products.minimum_stock"))
            ->when($stock === 'low', fn ($query) => $query
                ->whereRaw("{$stockTotalSql} > 0")
                ->whereRaw("{$stockTotalSql} <= products.minimum_stock"))
            ->when($stock === 'out', fn ($query) => $query->whereRaw("{$stockTotalSql} <= 0"))
            ->when($expiry === 'soon', fn ($query) => $query->whereHas('batches', fn ($batches) => $batches
                ->whereBetween('expiry_date', [today(), today()->addDays(30)])))
            ->when($expiry === 'expired', fn ($query) => $query->whereHas('batches', fn ($batches) => $batches
                ->whereDate('expiry_date', '<', today())))
            ->when($expiry === 'none', fn ($query) => $query->whereDoesntHave('batches', fn ($batches) => $batches
                ->whereNotNull('expiry_date')))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'stock_low', fn ($query) => $query->orderByRaw('COALESCE(stocks_sum_quantity, 0) asc'))
            ->when($sort === 'stock_high', fn ($query) => $query->orderByRaw('COALESCE(stocks_sum_quantity, 0) desc'))
            ->when($sort === 'price_low', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
            ->paginate(20)
            ->withQueryString();

        $categories = $company->categories()->orderBy('name')->get();
        $filters = compact('categoryId', 'status', 'stock', 'expiry', 'sort');
        $filterCount = collect([$search, $categoryId, $status, $stock, $expiry])->filter()->count();

        return view('products.index', compact(
            'company', 'products', 'search', 'categories', 'filters', 'filterCount',
            'productCount', 'activeCount', 'lowStockCount', 'outOfStockCount', 'expiringCount'
        ));
    }

    public function create(Company $company): View|RedirectResponse
    {
        if ($company->hasReachedProductLimit()) {
            return redirect()->route('companies.products.index', $company)
                ->withErrors(['product_limit' => __('The product limit for this company has been reached. Contact the platform administrator to increase it.')]);
        }

        return view('products.form', $this->formData($company, new Product));
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateProduct($request, $company);
        $image = $data['image'] ?? null;
        $batches = $data['batches'] ?? [];
        unset($data['image'], $data['batches']);

        $storedImage = $image?->store("companies/{$company->id}/products", 'public');
        if ($storedImage) {
            $data['image_path'] = $storedImage;
        }

        try {
            $product = DB::transaction(function () use ($company, $data, $batches) {
                $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);
                if ($lockedCompany->hasReachedProductLimit()) {
                    throw ValidationException::withMessages([
                        'product_limit' => __('The product limit for this company has been reached. Contact the platform administrator to increase it.'),
                    ]);
                }

                $barcode = $data['barcode'];
                unset($data['barcode']);
                $product = $company->products()->create($data);
                $product->barcodes()->create(['company_id' => $company->id, 'code' => $barcode, 'format' => 'code128', 'is_primary' => true]);
                $this->replaceBatches($company, $product, $batches);

                return $product;
            });
        } catch (Throwable $exception) {
            if ($storedImage) {
                Storage::disk('public')->delete($storedImage);
            }

            throw $exception;
        }

        return redirect()->route('companies.products.show', [$company, $product])->with('success', __('Product created.'));
    }

    public function show(Company $company, Product $product): View
    {
        $this->ensureCompany($company, $product);
        $product->load([
            'barcodes',
            'category',
            'stocks.location',
            'batches' => fn ($query) => $query
                ->orderByRaw('case when expiry_date is null then 1 else 0 end')
                ->orderBy('expiry_date')
                ->orderBy('id'),
        ]);
        $movements = $company->stockTransactions()->whereHas('lines', fn ($query) => $query->where('product_id', $product->id))
            ->with(['user', 'lines' => fn ($query) => $query->where('product_id', $product->id), 'lines.location'])
            ->latest('occurred_at')->limit(20)->get();

        return view('products.show', compact('company', 'product', 'movements'));
    }

    public function edit(Company $company, Product $product): View
    {
        $this->ensureCompany($company, $product);
        $product->load(['barcodes', 'batches']);

        return view('products.form', $this->formData($company, $product));
    }

    public function update(Request $request, Company $company, Product $product): RedirectResponse
    {
        $this->ensureCompany($company, $product);
        $data = $this->validateProduct($request, $company, $product);
        $image = $data['image'] ?? null;
        $batches = $data['batches'] ?? [];
        unset($data['image'], $data['batches']);

        $oldImage = $product->image_path;
        $storedImage = $image?->store("companies/{$company->id}/products", 'public');
        if ($storedImage) {
            $data['image_path'] = $storedImage;
        }

        try {
            DB::transaction(function () use ($company, $product, $data, $batches) {
                $barcode = $data['barcode'];
                unset($data['barcode']);
                $product->update($data);
                $product->barcodes()->updateOrCreate(['is_primary' => true], ['company_id' => $company->id, 'code' => $barcode, 'format' => 'code128']);
                $this->replaceBatches($company, $product, $batches);
            });
        } catch (Throwable $exception) {
            if ($storedImage) {
                Storage::disk('public')->delete($storedImage);
            }

            throw $exception;
        }

        if ($storedImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('companies.products.show', [$company, $product])->with('success', __('Product updated.'));
    }

    private function validateProduct(Request $request, Company $company, ?Product $product = null): array
    {
        $primaryBarcodeId = $product?->barcodes()->where('is_primary', true)->value('id');

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'name_ar' => ['nullable', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products')->where('company_id', $company->id)->ignore($product)],
            'barcode' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('product_barcodes', 'code')->where('company_id', $company->id)->ignore($primaryBarcodeId)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $company->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', 'string', 'max:30', Rule::exists('units', 'symbol')->where('company_id', $company->id)],
            'batches' => ['nullable', 'array', 'max:50'],
            'batches.*.batch_number' => ['required', 'string', 'max:100', 'distinct'],
            'batches.*.expiry_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cost' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function formData(Company $company, Product $product): array
    {
        return [
            'company' => $company,
            'product' => $product,
            'categories' => $company->categories()->orderBy('name')->get(),
            'units' => $company->units()->orderBy('name')->get(),
        ];
    }

    private function replaceBatches(Company $company, Product $product, array $batches): void
    {
        $product->batches()->delete();

        $product->batches()->createMany(collect($batches)->map(fn (array $batch) => [
            'company_id' => $company->id,
            'batch_number' => $batch['batch_number'],
            'expiry_date' => $batch['expiry_date'] ?? null,
        ])->all());
    }

    private function ensureCompany(Company $company, Product $product): void
    {
        abort_unless($product->company_id === $company->id, 404);
    }
}
