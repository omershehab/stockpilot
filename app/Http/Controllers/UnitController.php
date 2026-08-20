<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function store(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('units')->where('company_id', $company->id)],
            'name_ar' => ['nullable', 'string', 'max:80'],
            'symbol' => [
                'required',
                'string',
                'max:30',
                'regex:/^[\pL\pN._-]+$/u',
                Rule::unique('units')->where('company_id', $company->id),
            ],
        ]);

        $unit = $company->units()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['unit' => $unit], 201);
        }

        return back()->with('success', __('Unit created.'));
    }
}
