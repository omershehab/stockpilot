<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyUsageLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_control_product_user_and_warehouse_limits(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Limited Company');

        $this->actingAs($admin)->patch(route('admin.subscribers.limits.update', $company), [
            'product_limit' => 25,
            'user_limit' => 6,
            'warehouse_limit' => 4,
        ])->assertRedirect()->assertSessionHas('success');

        $company->refresh();
        $this->assertSame(25, $company->product_limit);
        $this->assertSame(6, $company->user_limit);
        $this->assertSame(4, $company->warehouse_limit);

        $this->actingAs($owner)->patch(route('admin.subscribers.limits.update', $company), [
            'product_limit' => 99,
        ])->assertForbidden();
    }

    public function test_product_and_warehouse_creation_stop_at_the_company_limits(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Resource Limits');
        $company->update(['product_limit' => 1, 'warehouse_limit' => 1]);
        $company->products()->create([
            'sku' => 'EXISTING', 'name' => 'Existing product', 'unit' => 'pcs',
            'cost' => 1, 'price' => 2, 'minimum_stock' => 0,
        ]);

        $this->actingAs($owner)->post(route('companies.products.store', $company), [
            'name' => 'Blocked product', 'sku' => 'BLOCKED', 'barcode' => 'BLOCKED001',
            'unit' => 'pcs', 'cost' => 1, 'price' => 2, 'minimum_stock' => 0,
        ])->assertSessionHasErrors('product_limit');

        $this->post(route('companies.warehouses.store', $company), [
            'name' => 'Blocked warehouse', 'code' => 'BLOCKED-WH', 'address' => 'Riyadh',
        ])->assertSessionHasErrors('warehouse_limit');

        $this->assertSame(1, $company->products()->count());
        $this->assertSame(1, $company->locations()->count());
    }

    public function test_direct_user_addition_and_invitation_acceptance_stop_at_the_limit(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'User Limit');
        $company->update(['user_limit' => 1]);

        $this->actingAs($owner)->post(route('companies.people.members.store', $company), [
            'name' => 'Blocked user', 'email' => 'blocked@example.com', 'role' => 'staff',
            'status' => 'active', 'access_mode' => 'role',
        ])->assertSessionHasErrors('user_limit');
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invitation = $company->invitations()->create([
            'invited_by_user_id' => $owner->id, 'email' => $invitee->email,
            'role' => 'staff', 'token' => Str::random(64), 'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($invitee)->post(route('invitations.accept', $invitation))
            ->assertSessionHasErrors('user_limit');

        $this->assertFalse($company->users()->whereKey($invitee->id)->exists());
        $this->assertNull($invitation->fresh()->accepted_at);
    }
}
