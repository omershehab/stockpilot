<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

class CompanyAccess
{
    public const PERMISSIONS = [
        'dashboard.view' => ['Dashboard', 'View company dashboard and summary metrics', 'لوحة التحكم'],
        'products.view' => ['View products', 'Browse products, stock levels, and product details', 'عرض الأصناف'],
        'products.manage' => ['Manage products', 'Create and edit products, prices, and barcodes', 'إدارة الأصناف'],
        'inventory.view' => ['View inventory activity', 'View stock movements and stock counts', 'عرض حركة المخزون'],
        'inventory.move' => ['Post stock movements', 'Receive, issue, adjust, and transfer inventory', 'تنفيذ حركة مخزون'],
        'inventory.count' => ['Manage stock counts', 'Start, enter, and finalize physical counts', 'إدارة الجرد'],
        'warehouses.manage' => ['Manage warehouses', 'Add and configure company warehouses and storage locations', 'إدارة المستودعات'],
        'labels.print' => ['Print labels', 'Preview and print product barcode labels', 'طباعة الملصقات'],
        'labels.manage' => ['Manage label designs', 'Create, edit, duplicate, and delete label templates', 'إدارة تصاميم الملصقات'],
        'people.manage' => ['Manage users & teams', 'Add users, change access, and organize teams', 'إدارة المستخدمين والفرق'],
    ];

    public const ROLE_LABELS = [
        'owner' => ['Owner', 'المالك'],
        'admin' => ['Admin', 'مسؤول'],
        'manager' => ['Manager', 'مدير'],
        'staff' => ['Staff', 'موظف'],
        'viewer' => ['Viewer', 'مشاهد'],
    ];

    public const ROLE_PERMISSIONS = [
        'owner' => ['*'],
        'admin' => ['*'],
        'manager' => [
            'dashboard.view', 'products.view', 'products.manage', 'inventory.view',
            'inventory.move', 'inventory.count', 'labels.print', 'labels.manage',
            'warehouses.manage',
        ],
        'staff' => [
            'dashboard.view', 'products.view', 'inventory.view', 'inventory.move',
            'inventory.count', 'labels.print',
        ],
        'viewer' => ['dashboard.view', 'products.view', 'inventory.view', 'labels.print'],
    ];

    public function permissionsFor(User $user, Company $company, mixed $membership = null): array
    {
        $membership ??= $user->companies()->whereKey($company->id)->first()?->pivot;

        if (! $membership || $membership->status !== 'active') {
            return [];
        }

        if (in_array($membership->role, ['owner', 'admin'], true)) {
            return array_keys(self::PERMISSIONS);
        }

        $personal = $this->decodePermissions($membership->permissions ?? null);
        $permissions = $personal === null
            ? (self::ROLE_PERMISSIONS[$membership->role] ?? [])
            : $personal;

        $teamPermissions = $user->teams()
            ->where('teams.company_id', $company->id)
            ->get(['teams.permissions'])
            ->flatMap(fn ($team) => $team->permissions ?? []);

        return collect($permissions)
            ->merge($teamPermissions)
            ->filter(fn ($permission) => array_key_exists($permission, self::PERMISSIONS))
            ->unique()
            ->values()
            ->all();
    }

    public function allows(User $user, Company $company, string $permission, mixed $membership = null): bool
    {
        return in_array($permission, $this->permissionsFor($user, $company, $membership), true);
    }

    public function groupedPermissions(): Collection
    {
        return collect(self::PERMISSIONS)->groupBy(fn ($definition, $key) => explode('.', $key)[0], preserveKeys: true);
    }

    private function decodePermissions(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
