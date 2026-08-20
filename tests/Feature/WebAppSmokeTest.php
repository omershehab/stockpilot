<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAppSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_tenant_pages_render_with_demo_inventory(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Smoke Test Company');
        $location = $company->locations()->first();
        $product = $company->products()->create([
            'sku' => 'SMOKE-1', 'name' => 'Smoke Product', 'name_ar' => 'صنف تجريبي',
            'unit' => 'pcs', 'cost' => 10, 'price' => 15, 'minimum_stock' => 2,
        ]);
        $product->barcodes()->create(['company_id' => $company->id, 'code' => '628100099999', 'format' => 'code128', 'is_primary' => true]);
        app(StockMovementService::class)->record($company, 'receive', [[
            'product_id' => $product->id, 'location_id' => $location->id, 'quantity_delta' => 5,
        ]], $user);

        $this->actingAs($user);

        $this->get(route('companies.dashboard', $company))->assertOk()->assertSee('Smoke Product');
        $this->get(route('companies.products.index', $company))->assertOk()->assertSee('SMOKE-1');
        $this->get(route('companies.products.show', [$company, $product]))->assertOk()->assertSee('628100099999');
        $this->get(route('companies.movements.index', $company))->assertOk()->assertSee('Stock movements');
        $this->get(route('companies.stock-counts.index', $company))->assertOk()->assertSee('Stock counts');
        $this->get(route('companies.labels.index', $company))->assertOk()->assertSee('Print labels');
        $this->get(route('companies.label-templates.index', $company))
            ->assertOk()
            ->assertSee('60×40')
            ->assertSee('Zoom in')
            ->assertSee('Resize element');
        $this->get(route('companies.products.label', [$company, $product]))->assertOk()->assertSee('628100099999');

        $template = $company->labelTemplates()->firstOrFail();
        $preview = $this->get(route('companies.labels.preview', $company).'?'.http_build_query([
            'template' => $template->id,
            'items' => [$product->id => 3],
        ]));
        $preview->assertOk()->assertSee('628100099999');
        $this->assertSame(3, substr_count($preview->getContent(), 'class="label"'));
    }

    public function test_custom_text_can_be_saved_and_printed_on_a_label(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Text Label Company');
        $product = $company->products()->create([
            'sku' => 'TEXT-1', 'name' => 'Text Product', 'unit' => 'pcs',
            'cost' => 5, 'price' => 8, 'minimum_stock' => 0,
        ]);
        $product->barcodes()->create([
            'company_id' => $company->id, 'code' => '628100012345',
            'format' => 'code128', 'is_primary' => true,
        ]);
        $product->batches()->create([
            'company_id' => $company->id,
            'batch_number' => 'LOT-2401',
            'expiry_date' => '2027-04-30',
        ]);
        $template = $company->labelTemplates()->firstOrFail();
        $layout = $template->layout;
        $layout[2]['content'] = '{{barcode}}';
        $layout[2]['barcodeFormat'] = 'datamatrix';
        $layout[2]['quietZone'] = 1;
        $layout[2]['inverted'] = true;
        $layout[] = [
            'id' => 'text-1', 'type' => 'text', 'content' => 'Handle with care · {{sku}} · {{batch}} · {{price_with_currency}}',
            'x' => 2, 'y' => 2, 'width' => 30, 'height' => 5,
            'fontSize' => 12, 'fontFamily' => 'tajawal', 'bold' => true,
            'italic' => true, 'underline' => true, 'textAlign' => 'right',
            'verticalAlign' => 'bottom', 'lineHeight' => 1.2, 'maxLines' => 2,
            'rotation' => 90, 'inverted' => true, 'visible' => true,
        ];
        $layout[] = [
            'id' => 'line-1', 'type' => 'line', 'x' => 2, 'y' => 7,
            'width' => 30, 'height' => 1, 'fontSize' => 10,
            'borderWidth' => 2, 'visible' => true,
        ];
        $layout[] = [
            'id' => 'box-1', 'type' => 'box', 'x' => 1, 'y' => 1,
            'width' => 32, 'height' => 8, 'fontSize' => 10,
            'borderWidth' => 1, 'visible' => true,
        ];

        $this->actingAs($user)->put(route('companies.label-templates.update', [$company, $template]), [
            'name' => $template->name,
            'width_mm' => $template->width_mm,
            'height_mm' => $template->height_mm,
            'is_default' => true,
            'layout' => json_encode($layout),
        ])->assertRedirect();

        $this->assertSame('Handle with care · {{sku}} · {{batch}} · {{price_with_currency}}', $template->fresh()->layout[4]['content']);
        $this->assertSame(2, $template->fresh()->layout[5]['borderWidth']);
        $this->assertSame('datamatrix', $template->fresh()->layout[2]['barcodeFormat']);
        $this->assertSame('tajawal', $template->fresh()->layout[4]['fontFamily']);
        $this->assertSame('right', $template->fresh()->layout[4]['textAlign']);
        $this->assertSame(90, $template->fresh()->layout[4]['rotation']);
        $this->assertTrue($template->fresh()->layout[4]['inverted']);
        $this->get(route('companies.products.label', [$company, $product]))
            ->assertOk()
            ->assertSee('Handle with care · TEXT-1 · LOT-2401 · 8.00')
            ->assertSee('currency-symbol', false)
            ->assertDontSee('8.00 SAR', false)
            ->assertSee('barcode barcode-datamatrix', false)
            ->assertSee('font-size:12pt', false)
            ->assertSee('transform:rotate(90deg)', false)
            ->assertSee('class="shape line"', false)
            ->assertSee('class="shape box"', false);

        foreach (['qr', 'code39', 'ean13', 'code93', 'ean8', 'upca', 'itf14', 'codabar'] as $format) {
            $layout[2]['barcodeFormat'] = $format;
            if ($format === 'ean8') {
                $layout[2]['content'] = '1234567';
            } elseif ($format === 'upca') {
                $layout[2]['content'] = '12345678901';
            } elseif ($format === 'itf14') {
                $layout[2]['content'] = '1234567890123';
            } elseif ($format === 'codabar') {
                $layout[2]['content'] = 'A123456A';
            } else {
                $layout[2]['content'] = '{{barcode}}';
            }
            $template->update(['layout' => $layout]);
            $this->get(route('companies.products.label', [$company, $product]))
                ->assertOk()
                ->assertSee("barcode barcode-{$format}", false)
                ->assertDontSee('<span class="barcode-error">', false);
        }
    }
}
