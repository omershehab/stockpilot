<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_open_another_company_workspace(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstCompany = app(CreateCompany::class)->handle($firstUser, 'First Company');
        app(CreateCompany::class)->handle($secondUser, 'Second Company');

        $this->actingAs($secondUser)
            ->get(route('companies.dashboard', $firstCompany))
            ->assertForbidden();
    }

    public function test_product_from_one_tenant_cannot_be_loaded_inside_another_tenant(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstCompany = app(CreateCompany::class)->handle($firstUser, 'First Company');
        $secondCompany = app(CreateCompany::class)->handle($secondUser, 'Second Company');
        $product = $firstCompany->products()->create([
            'sku' => 'TENANT-A-1', 'name' => 'Private product', 'unit' => 'pcs',
            'cost' => 1, 'price' => 2, 'minimum_stock' => 0,
        ]);

        $this->actingAs($secondUser)
            ->get(route('companies.products.show', [$secondCompany, $product]))
            ->assertNotFound();
    }

    public function test_batch_label_preview_ignores_products_from_another_tenant(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstCompany = app(CreateCompany::class)->handle($firstUser, 'First Company');
        $secondCompany = app(CreateCompany::class)->handle($secondUser, 'Second Company');
        $product = $firstCompany->products()->create([
            'sku' => 'PRIVATE-LABEL', 'name' => 'Private label product', 'unit' => 'pcs',
            'cost' => 1, 'price' => 2, 'minimum_stock' => 0,
        ]);
        $product->barcodes()->create([
            'company_id' => $firstCompany->id, 'code' => 'PRIVATE123',
            'format' => 'code128', 'is_primary' => true,
        ]);

        $response = $this->actingAs($secondUser)->get(
            route('companies.labels.preview', $secondCompany).'?'.http_build_query([
                'items' => [$product->id => 5],
            ])
        );

        $response->assertOk()->assertDontSee('PRIVATE123');
        $this->assertSame(0, substr_count($response->getContent(), 'class="label"'));
    }
}
