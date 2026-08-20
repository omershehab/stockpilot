<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Services\BarcodeSvgRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeLabelController extends Controller
{
    public function show(Request $request, Company $company, Product $product, BarcodeSvgRenderer $renderer): View
    {
        abort_unless($product->company_id === $company->id, 404);
        $product->load(['barcodes', 'batches', 'category']);
        abort_unless($product->primary_barcode, 404, __('This product has no barcode.'));
        $copies = min(max((int) $request->query('copies', 1), 1), 100);
        $template = $company->labelTemplates()->find($request->integer('template'))
            ?? $company->labelTemplates()->where('is_default', true)->first()
            ?? $company->labelTemplates()->firstOrFail();
        $barcodeSvgs = $renderer->renderForProduct($product, $company, $template->layout);

        return view('labels.product', compact('company', 'product', 'copies', 'barcodeSvgs', 'template'));
    }
}
