<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_people_management_and_add_a_user(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Access Test Company');

        $this->actingAs($owner)
            ->get(route('companies.people.index', $company))
            ->assertOk()
            ->assertSee('Users &amp; teams', false);

        $this->post(route('companies.people.members.store', $company), [
            'name' => 'Warehouse User',
            'email' => 'warehouse@example.com',
            'role' => 'staff',
            'status' => 'active',
            'access_mode' => 'role',
        ])->assertRedirect()->assertSessionHas('temporary_password');

        $user = User::where('email', 'warehouse@example.com')->firstOrFail();
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'staff',
            'status' => 'active',
            'permissions' => null,
        ]);
    }

    public function test_viewer_can_read_inventory_but_cannot_manage_products_or_people(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Viewer Company');
        $company->users()->attach($viewer, ['role' => 'viewer', 'status' => 'active']);

        $this->actingAs($viewer)
            ->get(route('companies.products.index', $company))
            ->assertOk();
        $this->get(route('companies.products.create', $company))->assertForbidden();
        $this->get(route('companies.people.index', $company))->assertForbidden();
    }

    public function test_custom_access_replaces_role_preset_and_team_access_is_inherited(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Team Access Company');
        $company->users()->attach($staff, [
            'role' => 'staff',
            'status' => 'active',
            'permissions' => json_encode([]),
        ]);

        $this->actingAs($staff)
            ->get(route('companies.products.index', $company))
            ->assertForbidden();

        $team = $company->teams()->create([
            'name' => 'Catalog managers',
            'permissions' => ['products.view', 'products.manage'],
        ]);
        $team->users()->attach($staff);

        $this->get(route('companies.products.index', $company))->assertOk();
        $this->get(route('companies.products.create', $company))->assertOk();
        $this->get(route('companies.people.index', $company))->assertForbidden();
    }

    public function test_inactive_member_is_blocked_and_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $inactive = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Protected Company');
        $company->users()->attach($inactive, ['role' => 'admin', 'status' => 'inactive']);

        $this->actingAs($inactive)
            ->get(route('companies.dashboard', $company))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('companies.people.members.destroy', [$company, $owner]))
            ->assertForbidden();

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }
}
