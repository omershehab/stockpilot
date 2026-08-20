<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockCountController extends Controller
{
    public function index(Company $company): View
    {
        $counts = $company->stockCounts()->with('location')->withCount('lines')->latest()->paginate(20);
        $locations = $company->locations()->where('is_active', true)->orderBy('name')->get();

        return view('stock-counts.index', compact('company', 'counts', 'locations'));
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $company->id)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = DB::transaction(function () use ($request, $company, $data) {
            $reference = 'COUNT-'.now()->format('Ymd').'-'.str_pad((string) ($company->stockCounts()->count() + 1), 4, '0', STR_PAD_LEFT);
            $count = $company->stockCounts()->create([
                'location_id' => $data['location_id'],
                'started_by' => $request->user()->id,
                'reference' => $reference,
                'status' => 'counting',
                'snapshot_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $stocks = $company->stocks()->where('location_id', $data['location_id'])->pluck('quantity', 'product_id');
            foreach ($company->products()->where('is_active', true)->get(['id']) as $product) {
                $count->lines()->create(['product_id' => $product->id, 'expected_quantity' => $stocks[$product->id] ?? 0]);
            }

            return $count;
        });

        return redirect()->route('companies.stock-counts.show', [$company, $count])->with('success', __('Stock count started.'));
    }

    public function show(Company $company, StockCount $stockCount): View
    {
        $this->ensureCompany($company, $stockCount);
        $stockCount->load(['location', 'lines.product.barcodes']);

        return view('stock-counts.show', compact('company', 'stockCount'));
    }

    public function updateLine(Request $request, Company $company, StockCount $stockCount, StockCountLine $line): RedirectResponse
    {
        $this->ensureCompany($company, $stockCount);
        abort_unless($line->stock_count_id === $stockCount->id, 404);
        abort_if($stockCount->status === 'completed', 422, __('This count is already completed.'));
        $data = $request->validate(['counted_quantity' => ['required', 'numeric', 'min:0']]);
        $line->update([
            'counted_quantity' => $data['counted_quantity'],
            'variance' => round((float) $data['counted_quantity'] - (float) $line->expected_quantity, 3),
        ]);

        return back()->with('success', __('Count updated.'));
    }

    public function finalize(Request $request, Company $company, StockCount $stockCount, StockMovementService $service): RedirectResponse
    {
        $this->ensureCompany($company, $stockCount);
        abort_if($stockCount->status === 'completed', 422, __('This count is already completed.'));
        $stockCount->load('lines');

        if ($stockCount->lines->contains(fn ($line) => $line->counted_quantity === null)) {
            throw ValidationException::withMessages(['count' => 'Every item must be counted before finalizing.']);
        }

        DB::transaction(function () use ($request, $company, $stockCount, $service) {
            $adjustments = $stockCount->lines->filter(fn ($line) => (float) $line->variance !== 0.0)->map(fn ($line) => [
                'product_id' => $line->product_id,
                'location_id' => $stockCount->location_id,
                'quantity_delta' => $line->variance,
            ])->values()->all();

            if ($adjustments) {
                $service->record($company, 'count_adjustment', $adjustments, $request->user(), $stockCount->reference, 'Inventory count adjustment');
            }

            $stockCount->update(['status' => 'completed', 'approved_by' => $request->user()->id, 'completed_at' => now()]);
        });

        return back()->with('success', __('Inventory count finalized and variances applied.'));
    }

    private function ensureCompany(Company $company, StockCount $stockCount): void
    {
        abort_unless($stockCount->company_id === $company->id, 404);
    }
}
