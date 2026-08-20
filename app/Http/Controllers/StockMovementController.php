<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Company $company): View
    {
        $products = $company->products()->with('barcodes')->orderBy('name')->get();
        $locations = $company->locations()->where('is_active', true)->orderBy('name')->get();
        $transactions = $company->stockTransactions()->with(['user', 'lines.product', 'lines.location'])->latest('occurred_at')->paginate(20);

        return view('movements.index', compact('company', 'products', 'locations', 'transactions'));
    }

    public function store(Request $request, Company $company, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['receive', 'issue', 'adjust', 'transfer'])],
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $company->id)],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('company_id', $company->id)],
            'destination_location_id' => ['nullable', 'different:location_id', Rule::exists('locations', 'id')->where('company_id', $company->id)],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quantity = abs((float) $data['quantity']);
        $lines = match ($data['type']) {
            'receive' => [['product_id' => $data['product_id'], 'location_id' => $data['location_id'], 'quantity_delta' => $quantity]],
            'issue' => [['product_id' => $data['product_id'], 'location_id' => $data['location_id'], 'quantity_delta' => -$quantity]],
            'adjust' => [['product_id' => $data['product_id'], 'location_id' => $data['location_id'], 'quantity_delta' => (float) $data['quantity']]],
            'transfer' => $this->transferLines($data, $quantity),
        };

        $service->record($company, $data['type'], $lines, $request->user(), $data['reference'] ?? null, $data['notes'] ?? null);

        return back()->with('success', __('Stock transaction posted successfully.'));
    }

    private function transferLines(array $data, float $quantity): array
    {
        if (empty($data['destination_location_id'])) {
            abort(422, __('Destination location is required for a transfer.'));
        }

        return [
            ['product_id' => $data['product_id'], 'location_id' => $data['location_id'], 'quantity_delta' => -$quantity],
            ['product_id' => $data['product_id'], 'location_id' => $data['destination_location_id'], 'quantity_delta' => $quantity],
        ];
    }
}
