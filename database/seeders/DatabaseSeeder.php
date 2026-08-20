<?php

namespace Database\Seeders;

use App\Actions\CreateCompany;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'demo@stockpilot.test',
            'password' => 'password',
            'is_platform_admin' => true,
        ]);

        $company = app(CreateCompany::class)->handle($user, 'Riyadh Trading Demo');
        $category = $company->categories()->where('name', 'Supplies')->first();
        $location = $company->locations()->first();
        $items = [
            ['sku' => 'PAP-A4-80', 'barcode' => '628100000001', 'name' => 'A4 Copy Paper 80gsm', 'name_ar' => 'ورق تصوير A4', 'cost' => 18.50, 'price' => 24.00, 'minimum_stock' => 20, 'quantity' => 84],
            ['sku' => 'LBL-60X40', 'barcode' => '628100000002', 'name' => 'Thermal Labels 60×40', 'name_ar' => 'ملصقات حرارية 60×40', 'cost' => 12.00, 'price' => 18.00, 'minimum_stock' => 15, 'quantity' => 42],
            ['sku' => 'SCN-USB-01', 'barcode' => '628100000003', 'name' => 'USB Barcode Scanner', 'name_ar' => 'قارئ باركود USB', 'cost' => 95.00, 'price' => 145.00, 'minimum_stock' => 5, 'quantity' => 12],
            ['sku' => 'INK-BLK-01', 'barcode' => '628100000004', 'name' => 'Black Printer Ink', 'name_ar' => 'حبر طابعة أسود', 'cost' => 42.00, 'price' => 62.00, 'minimum_stock' => 8, 'quantity' => 6],
            ['sku' => 'BOX-MED-01', 'barcode' => '628100000005', 'name' => 'Medium Shipping Box', 'name_ar' => 'كرتون شحن متوسط', 'cost' => 2.50, 'price' => 5.00, 'minimum_stock' => 50, 'quantity' => 120],
        ];

        $lines = [];
        foreach ($items as $item) {
            $product = $company->products()->create([
                'category_id' => $category?->id,
                'sku' => $item['sku'],
                'name' => $item['name'],
                'name_ar' => $item['name_ar'],
                'unit' => 'pcs',
                'cost' => $item['cost'],
                'price' => $item['price'],
                'minimum_stock' => $item['minimum_stock'],
            ]);
            $product->barcodes()->create(['company_id' => $company->id, 'code' => $item['barcode'], 'format' => 'code128', 'is_primary' => true]);
            $lines[] = ['product_id' => $product->id, 'location_id' => $location->id, 'quantity_delta' => $item['quantity']];
        }

        app(StockMovementService::class)->record($company, 'receive', $lines, $user, 'OPENING-BALANCE', 'Demo opening inventory');
    }
}
