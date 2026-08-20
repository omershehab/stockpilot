<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_routes_require_authentication_and_admin_flag(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_platform_admin_can_monitor_overview_subscribers_products_and_users(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $owner = User::factory()->create(['name' => 'Acme Owner']);
        $company = app(CreateCompany::class)->handle($owner, 'Acme Subscriber');
        $product = $company->products()->create([
            'sku' => 'ADMIN-MONITOR-1',
            'name' => 'Admin Monitored Product',
            'unit' => 'pcs',
            'cost' => 12,
            'price' => 20,
            'minimum_stock' => 2,
        ]);
        $product->barcodes()->create([
            'company_id' => $company->id,
            'code' => 'ADMIN123456',
            'format' => 'code128',
            'is_primary' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform overview')
            ->assertSee('Acme Subscriber');
        $this->get(route('admin.subscribers.index'))
            ->assertOk()
            ->assertSee('Acme Subscriber');
        $this->get(route('admin.subscribers.show', $company))
            ->assertOk()
            ->assertSee('Acme Owner')
            ->assertSee('Admin Monitored Product');
        $this->get(route('admin.products.index').'?search=ADMIN123456')
            ->assertOk()
            ->assertSee('Admin Monitored Product')
            ->assertSee('Acme Subscriber');
        $this->get(route('admin.users.index').'?search=Acme+Owner')
            ->assertOk()
            ->assertSee('Acme Owner')
            ->assertSee('Acme Subscriber');
    }

    public function test_platform_admin_with_no_company_is_redirected_to_admin_console(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
