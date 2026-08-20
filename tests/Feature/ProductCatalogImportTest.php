<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_creates_products_batches_categories_and_opening_stock(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Import Company');
        $company->update(['product_limit' => 10]);
        $csv = implode("\n", [
            'sku,name,name_ar,barcode,category,unit,cost,price,minimum_stock,is_active,batch_number,expiry_date,initial_quantity,warehouse_code',
            'MED-001,Vitamin C,فيتامين سي,628100000201,Medicine,pcs,12.50,19.95,5,yes,LOT-A,2027-08-31,15,MAIN',
            'BOX-001,Shipping Box,,628100000202,Packing,box,2.50,5.00,10,no,,,0,',
        ]);

        $this->actingAs($owner)
            ->post(route('companies.products.import.store', $company), [
                'import_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('companies.products.index', $company))
            ->assertSessionHas('success');

        $vitamin = $company->products()->where('sku', 'MED-001')->firstOrFail();
        $box = $company->products()->where('sku', 'BOX-001')->firstOrFail();
        $this->assertSame('Medicine', $vitamin->category->name);
        $this->assertSame('LOT-A', $vitamin->batches()->firstOrFail()->batch_number);
        $this->assertSame('15.000', $vitamin->stocks()->firstOrFail()->quantity);
        $this->assertFalse($box->is_active);
        $this->assertDatabaseHas('product_barcodes', ['company_id' => $company->id, 'code' => '628100000201']);
        $this->assertDatabaseHas('stock_transactions', ['company_id' => $company->id, 'type' => 'receive']);
    }

    public function test_import_is_all_or_nothing_and_respects_company_product_limit(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Limited Import');
        $company->update(['product_limit' => 1]);
        $csv = implode("\n", [
            'sku,name,barcode,unit,cost,price',
            'ONE-001,First Product,628100000301,pcs,1,2',
            'TWO-001,Second Product,628100000302,pcs,1,2',
        ]);

        $this->actingAs($owner)
            ->post(route('companies.products.import.store', $company), [
                'import_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertSessionHasErrors('import');

        $this->assertSame(0, $company->products()->count());

        $invalidCsv = implode("\n", [
            'sku,name,barcode,unit,cost,price',
            'GOOD-001,Good Product,628100000401,pcs,1,2',
            'BAD-001,Bad Product,invalid barcode!,missing,1,2',
        ]);
        $company->update(['product_limit' => 10]);

        $this->post(route('companies.products.import.store', $company), [
            'import_file' => UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv),
        ])->assertSessionHasErrors('import');

        $this->assertSame(0, $company->products()->count());
    }

    public function test_sample_xlsx_can_be_downloaded_and_imported(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Spreadsheet Import');

        $this->actingAs($owner)
            ->get(route('companies.products.import.template', $company))
            ->assertOk()
            ->assertDownload('products-import-template.xlsx');

        $upload = UploadedFile::fake()->createWithContent(
            'products.xlsx',
            file_get_contents(resource_path('templates/products-import-template.xlsx'))
        );

        $this->post(route('companies.products.import.store', $company), ['import_file' => $upload])
            ->assertRedirect(route('companies.products.index', $company));

        $this->assertSame(2, $company->products()->count());
        $this->assertDatabaseHas('products', ['company_id' => $company->id, 'sku' => 'PAP-A4-80']);
        $this->assertDatabaseHas('products', ['company_id' => $company->id, 'sku' => 'INK-BLK-01']);
    }

    public function test_catalog_filters_by_stock_status_category_expiry_and_search(): void
    {
        $owner = User::factory()->create();
        $company = app(CreateCompany::class)->handle($owner, 'Filtered Catalog');
        $location = $company->locations()->firstOrFail();
        $category = $company->categories()->where('name', 'Supplies')->firstOrFail();

        $healthy = $company->products()->create(['category_id' => $category->id, 'sku' => 'HEALTHY', 'name' => 'Healthy Item', 'unit' => 'pcs', 'cost' => 1, 'price' => 2, 'minimum_stock' => 5]);
        $healthy->barcodes()->create(['company_id' => $company->id, 'code' => 'HEALTHY001', 'is_primary' => true]);
        $low = $company->products()->create(['sku' => 'LOW', 'name' => 'Low Item', 'unit' => 'pcs', 'cost' => 1, 'price' => 2, 'minimum_stock' => 5, 'is_active' => false]);
        $low->barcodes()->create(['company_id' => $company->id, 'code' => 'LOW001', 'is_primary' => true]);
        $low->batches()->create(['company_id' => $company->id, 'batch_number' => 'SOON', 'expiry_date' => today()->addDays(10)]);
        app(StockMovementService::class)->record($company, 'receive', [
            ['product_id' => $healthy->id, 'location_id' => $location->id, 'quantity_delta' => 20],
            ['product_id' => $low->id, 'location_id' => $location->id, 'quantity_delta' => 2],
        ], $owner);

        $this->actingAs($owner)
            ->get(route('companies.products.index', [$company, 'stock' => 'low']))
            ->assertOk()->assertSee('Low Item')->assertDontSee('Healthy Item');
        $this->get(route('companies.products.index', [$company, 'status' => 'inactive']))
            ->assertOk()->assertSee('Low Item')->assertDontSee('Healthy Item');
        $this->get(route('companies.products.index', [$company, 'category' => $category->id]))
            ->assertOk()->assertSee('Healthy Item')->assertDontSee('Low Item');
        $this->get(route('companies.products.index', [$company, 'expiry' => 'soon']))
            ->assertOk()->assertSee('Low Item')->assertDontSee('Healthy Item');
        $this->get(route('companies.products.index', [$company, 'search' => 'HEALTHY001']))
            ->assertOk()->assertSee('Healthy Item')->assertDontSee('Low Item');
    }
}
