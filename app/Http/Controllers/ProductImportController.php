<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    public function store(Request $request, Company $company, ProductImportService $importer): RedirectResponse
    {
        $request->validate([
            'import_file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            return back()->withErrors([
                'import_file' => __('Please upload a CSV or XLSX file.'),
            ]);
        }

        $count = $importer->import($company, $file, $request->user());

        return redirect()->route('companies.products.index', $company)
            ->with('success', trans_choice('{1} :count product imported successfully.|[2,*] :count products imported successfully.', $count, ['count' => $count]));
    }

    public function template(Company $company): BinaryFileResponse
    {
        $path = resource_path('templates/products-import-template.xlsx');
        abort_unless(is_file($path), 404);

        return response()->download($path, 'products-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
