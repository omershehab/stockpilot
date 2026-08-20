<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories')->where('company_id', $company->id)],
            'name_ar' => ['nullable', 'string', 'max:120'],
        ]);

        $category = $company->categories()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['category' => $category], 201);
        }

        return back()->with('success', __('Category created.'));
    }
}
