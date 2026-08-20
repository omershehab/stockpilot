<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_and_add_a_company_warehouse(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Warehouse Company');

        $this->actingAs($owner)
            ->get(route('companies.warehouses.index', $company))
            ->assertOk()
            ->assertSee('Main Warehouse')
            ->assertSee('Add warehouse');

        $this->actingAs($owner)
            ->post(route('companies.warehouses.store', $company), [
                'name' => 'Jeddah Warehouse',
                'code' => 'jed-01',
                'address' => 'Jeddah Industrial City',
            ])
            ->assertRedirect(route('companies.warehouses.index', $company));

        $this->assertDatabaseHas('locations', [
            'company_id' => $company->id,
            'name' => 'Jeddah Warehouse',
            'code' => 'JED-01',
            'address' => 'Jeddah Industrial City',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_manage_warehouses_but_viewer_cannot(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Role Company');
        $manager = User::factory()->create();
        $viewer = User::factory()->create();
        $company->users()->attach($manager, ['role' => 'manager', 'status' => 'active']);
        $company->users()->attach($viewer, ['role' => 'viewer', 'status' => 'active']);

        $this->actingAs($manager)
            ->get(route('companies.warehouses.index', $company))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('companies.warehouses.index', $company))
            ->assertForbidden();
    }

    public function test_a_warehouse_from_another_company_cannot_be_edited(): void
    {
        $owner = User::factory()->create();
        $firstCompany = app(CreateCompany::class)->handle($owner, 'First Company');
        $secondCompany = app(CreateCompany::class)->handle($owner, 'Second Company');
        $foreignWarehouse = $firstCompany->locations()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('companies.warehouses.edit', [$secondCompany, $foreignWarehouse]))
            ->assertNotFound();
    }

    public function test_owner_can_change_the_default_warehouse(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Default Company');
        $mainWarehouse = $company->locations()->firstOrFail();
        $secondWarehouse = $company->locations()->create([
            'name' => 'Eastern Warehouse',
            'code' => 'EAST',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->put(route('companies.warehouses.update', [$company, $secondWarehouse]), [
                'name' => 'Eastern Warehouse',
                'code' => 'EAST',
                'is_default' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('companies.warehouses.index', $company));

        $this->assertFalse($mainWarehouse->fresh()->is_default);
        $this->assertTrue($secondWarehouse->fresh()->is_default);
        $this->assertTrue($secondWarehouse->fresh()->is_active);
    }

    public function test_default_warehouse_cannot_be_deactivated(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Protected Default Company');
        $warehouse = $company->locations()->firstOrFail();

        $this->actingAs($owner)
            ->from(route('companies.warehouses.edit', [$company, $warehouse]))
            ->put(route('companies.warehouses.update', [$company, $warehouse]), [
                'name' => $warehouse->name,
                'code' => $warehouse->code,
            ])
            ->assertRedirect(route('companies.warehouses.edit', [$company, $warehouse]))
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($warehouse->fresh()->is_default);
        $this->assertTrue($warehouse->fresh()->is_active);
    }
}
