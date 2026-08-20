<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Company $company): View
    {
        $warehouses = $company->locations()
            ->withCount('stocks')
            ->withSum('stocks', 'quantity')
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('warehouses.index', compact('company', 'warehouses'));
    }

    public function create(Company $company): View|RedirectResponse
    {
        if ($company->hasReachedWarehouseLimit()) {
            return redirect()->route('companies.warehouses.index', $company)
                ->withErrors(['warehouse_limit' => __('The warehouse limit for this company has been reached. Contact the platform administrator to increase it.')]);
        }

        return view('warehouses.form', [
            'company' => $company,
            'warehouse' => new Location(['is_active' => true]),
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request, $company);
        $data['is_default'] = $request->boolean('is_default') || ! $company->locations()->exists();
        $data['is_active'] = true;

        DB::transaction(function () use ($company, $data) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);
            if ($lockedCompany->hasReachedWarehouseLimit()) {
                throw ValidationException::withMessages([
                    'warehouse_limit' => __('The warehouse limit for this company has been reached. Contact the platform administrator to increase it.'),
                ]);
            }

            if ($data['is_default']) {
                $company->locations()->update(['is_default' => false]);
            }

            $company->locations()->create($data);
        });

        return redirect()->route('companies.warehouses.index', $company)
            ->with('success', __('Warehouse created successfully.'));
    }

    public function edit(Company $company, Location $warehouse): View
    {
        $this->ensureCompany($company, $warehouse);

        return view('warehouses.form', compact('company', 'warehouse'));
    }

    public function update(Request $request, Company $company, Location $warehouse): RedirectResponse
    {
        $this->ensureCompany($company, $warehouse);
        $data = $this->validated($request, $company, $warehouse);
        $makeDefault = $request->boolean('is_default');
        $active = $request->boolean('is_active');

        if ($warehouse->is_default && ! $active) {
            return back()->withInput()->withErrors([
                'is_active' => __('Choose another default warehouse before deactivating this one.'),
            ]);
        }

        DB::transaction(function () use ($company, $warehouse, $data, $makeDefault, $active) {
            if ($makeDefault) {
                $company->locations()->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            }

            $warehouse->update([
                ...$data,
                'is_default' => $makeDefault || $warehouse->is_default,
                'is_active' => $makeDefault ? true : $active,
            ]);
        });

        return redirect()->route('companies.warehouses.index', $company)
            ->with('success', __('Warehouse updated successfully.'));
    }

    private function validated(Request $request, Company $company, ?Location $warehouse = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('locations')->where('company_id', $company->id)->ignore($warehouse),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function ensureCompany(Company $company, Location $warehouse): void
    {
        abort_unless($warehouse->company_id === $company->id, 404);
    }
}
