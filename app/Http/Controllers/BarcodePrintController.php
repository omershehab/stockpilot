<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\BarcodeSvgRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodePrintController extends Controller
{
    public function index(Company $company): View
    {
        $templates = $company->labelTemplates()->orderByDesc('is_default')->orderBy('name')->get();
        $products = $company->products()
            ->where('is_active', true)
            ->with(['barcodes', 'batches', 'category'])
            ->orderBy('name')
            ->get()
            ->filter(fn ($product) => $product->primary_barcode)
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'nameAr' => $product->name_ar,
                'sku' => $product->sku,
                'barcode' => $product->primary_barcode->code,
            ])
            ->values();

        return view('labels.index', compact('company', 'templates', 'products'));
    }

    public function preview(Request $request, Company $company, BarcodeSvgRenderer $renderer): View
    {
        $template = $company->labelTemplates()->find($request->integer('template'))
            ?? $company->labelTemplates()->where('is_default', true)->first()
            ?? $company->labelTemplates()->firstOrFail();

        $requestedItems = collect(array_slice((array) $request->query('items', []), 0, 100, true))
            ->filter(fn ($copies, $productId) => ctype_digit((string) $productId) && is_numeric($copies))
            ->map(fn ($copies) => min(100, max(1, (int) $copies)));

        $products = $company->products()
            ->with(['barcodes', 'batches', 'category'])
            ->whereIn('id', $requestedItems->keys())
            ->get()
            ->keyBy('id');

        $remainingLabels = 500;
        $entries = $requestedItems->map(function (int $copies, int|string $productId) use ($company, $products, $renderer, $template, &$remainingLabels) {
            $product = $products->get((int) $productId);
            if (! $product?->primary_barcode || $remainingLabels < 1) {
                return null;
            }

            $copies = min($copies, $remainingLabels);
            $remainingLabels -= $copies;

            return [
                'product' => $product,
                'copies' => $copies,
                'barcodeSvgs' => $renderer->renderForProduct($product, $company, $template->layout),
            ];
        })->filter()->values();

        $totalLabels = $entries->sum('copies');
        $embedded = $request->boolean('embedded');

        return view('labels.batch', compact('company', 'template', 'entries', 'totalLabels', 'embedded'));
    }
}
