<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_multiple_batches_expiries_and_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Tracked Products');

        $response = $this->actingAs($user)->post(route('companies.products.store', $company), [
            'name' => 'Vitamin C',
            'name_ar' => 'فيتامين سي',
            'sku' => 'VIT-C-100',
            'barcode' => '628100055555',
            'category_id' => $company->categories()->first()->id,
            'unit' => 'pcs',
            'batches' => [
                ['batch_number' => 'LOT-2026-08-A', 'expiry_date' => '2027-08-31'],
                ['batch_number' => 'LOT-2026-08-B', 'expiry_date' => '2028-01-15'],
            ],
            'image' => UploadedFile::fake()->image('vitamin-c.jpg', 600, 400),
            'cost' => 12.50,
            'price' => 19.95,
            'minimum_stock' => 5,
            'is_active' => 1,
        ]);

        $product = $company->products()->where('sku', 'VIT-C-100')->firstOrFail();

        $response->assertRedirect(route('companies.products.show', [$company, $product]));
        $this->assertSame(2, $product->batches()->count());
        $this->assertSame('2027-08-31', $product->batches()->where('batch_number', 'LOT-2026-08-A')->firstOrFail()->expiry_date->format('Y-m-d'));
        $this->assertSame('2028-01-15', $product->batches()->where('batch_number', 'LOT-2026-08-B')->firstOrFail()->expiry_date->format('Y-m-d'));
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $this->actingAs($user)
            ->get(route('companies.products.show', [$company, $product]))
            ->assertOk()
            ->assertSee('LOT-2026-08-A')
            ->assertSee('LOT-2026-08-B');
    }

    public function test_updating_a_product_replaces_its_batch_list(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Updated Batches');
        $product = $company->products()->create([
            'sku' => 'BATCHED-1', 'name' => 'Batched Product', 'unit' => 'pcs',
            'cost' => 5, 'price' => 8, 'minimum_stock' => 0,
        ]);
        $product->barcodes()->create([
            'company_id' => $company->id, 'code' => 'BATCHED001',
            'format' => 'code128', 'is_primary' => true,
        ]);
        $product->batches()->create([
            'company_id' => $company->id,
            'batch_number' => 'OLD-BATCH',
            'expiry_date' => '2027-01-01',
        ]);

        $this->actingAs($user)->put(route('companies.products.update', [$company, $product]), [
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => 'BATCHED001',
            'unit' => 'pcs',
            'cost' => 5,
            'price' => 8,
            'minimum_stock' => 0,
            'is_active' => 1,
            'batches' => [
                ['batch_number' => 'NEW-BATCH-1', 'expiry_date' => '2028-01-01'],
                ['batch_number' => 'NEW-BATCH-2', 'expiry_date' => '2028-06-30'],
            ],
        ])->assertRedirect(route('companies.products.show', [$company, $product]));

        $this->assertDatabaseMissing('product_batches', ['product_id' => $product->id, 'batch_number' => 'OLD-BATCH']);
        $this->assertSame(['NEW-BATCH-1', 'NEW-BATCH-2'], $product->batches()->orderBy('batch_number')->pluck('batch_number')->all());
    }

    public function test_category_and_unit_can_be_created_from_the_product_page(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Configurable Products');

        $this->actingAs($user)
            ->get(route('companies.products.create', $company))
            ->assertOk()
            ->assertSee('Add category')
            ->assertSee('Add unit')
            ->assertSee('Batch number')
            ->assertSee('Choose image');

        $this->postJson(route('companies.categories.store', $company), [
            'name' => 'Medicine',
            'name_ar' => 'دواء',
        ])->assertCreated()->assertJsonPath('category.name', 'Medicine');

        $this->postJson(route('companies.units.store', $company), [
            'name' => 'Carton',
            'name_ar' => 'كرتون',
            'symbol' => 'ctn',
        ])->assertCreated()->assertJsonPath('unit.symbol', 'ctn');

        $this->assertDatabaseHas('categories', ['company_id' => $company->id, 'name' => 'Medicine']);
        $this->assertDatabaseHas('units', ['company_id' => $company->id, 'name' => 'Carton', 'symbol' => 'ctn']);
    }

    public function test_new_companies_receive_the_default_units(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Default Units');

        $this->assertSame(['box', 'kg', 'liter', 'm', 'pcs'], $company->units()->orderBy('symbol')->pluck('symbol')->all());
    }
}
