<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\Stock;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_movements_update_snapshot_and_keep_ledger_lines(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Ledger Company');
        $location = $company->locations()->first();
        $product = $company->products()->create(['sku' => 'SKU-1', 'name' => 'Item', 'unit' => 'pcs', 'cost' => 4, 'price' => 6, 'minimum_stock' => 0]);
        $service = app(StockMovementService::class);

        $service->record($company, 'receive', [['product_id' => $product->id, 'location_id' => $location->id, 'quantity_delta' => 10]], $user);
        $service->record($company, 'issue', [['product_id' => $product->id, 'location_id' => $location->id, 'quantity_delta' => -3]], $user);

        $this->assertSame(7.0, (float) Stock::first()->quantity);
        $this->assertDatabaseCount('stock_transactions', 2);
        $this->assertDatabaseCount('stock_transaction_lines', 2);
    }

    public function test_stock_cannot_become_negative(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Safe Stock Company');
        $location = $company->locations()->first();
        $product = $company->products()->create(['sku' => 'SKU-2', 'name' => 'Item', 'unit' => 'pcs', 'cost' => 1, 'price' => 1, 'minimum_stock' => 0]);

        $this->expectException(ValidationException::class);
        app(StockMovementService::class)->record($company, 'issue', [['product_id' => $product->id, 'location_id' => $location->id, 'quantity_delta' => -1]], $user);
    }
}
