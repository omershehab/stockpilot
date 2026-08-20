<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_page_exposes_template_management_actions(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Template Actions Company');

        $this->actingAs($user)
            ->get(route('companies.labels.index', $company))
            ->assertOk()
            ->assertSee('Duplicate template')
            ->assertSee('Edit template')
            ->assertSee('Delete template');
    }

    public function test_template_can_be_duplicated_and_deleted_safely(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Template Management Company');
        $template = $company->labelTemplates()->firstOrFail();

        $this->actingAs($user)
            ->post(route('companies.label-templates.duplicate', [$company, $template]))
            ->assertSessionHas('success');

        $copy = $company->labelTemplates()->where('id', '!=', $template->id)->firstOrFail();
        $this->assertSame($template->layout, $copy->layout);
        $this->assertSame($template->width_mm, $copy->width_mm);
        $this->assertSame($template->height_mm, $copy->height_mm);
        $this->assertFalse($copy->is_default);

        $this->delete(route('companies.label-templates.destroy', [$company, $template]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('label_templates', ['id' => $template->id]);
        $this->assertTrue($copy->fresh()->is_default);

        $this->delete(route('companies.label-templates.destroy', [$company, $copy]))
            ->assertSessionHasErrors('template');
        $this->assertDatabaseHas('label_templates', ['id' => $copy->id]);
    }

    public function test_designer_supports_product_variables_text_styles_and_real_datamatrix_preview(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Advanced Labels Company');
        $product = $company->products()->create([
            'sku' => 'ADV-100',
            'name' => 'Advanced Product',
            'name_ar' => 'صنف متقدم',
            'description' => 'Variable content product',
            'unit' => 'pcs',
            'cost' => 11,
            'price' => 18.5,
            'minimum_stock' => 1,
        ]);
        $product->barcodes()->create([
            'company_id' => $company->id,
            'code' => '628100077777',
            'format' => 'code128',
            'is_primary' => true,
        ]);
        $product->batches()->create([
            'company_id' => $company->id,
            'batch_number' => 'ADV-BATCH',
            'expiry_date' => '2028-01-15',
        ]);
        $template = $company->labelTemplates()->firstOrFail();

        $this->actingAs($user)
            ->get(route('companies.label-templates.index', $company))
            ->assertOk()
            ->assertSee('Preview product')
            ->assertSee('Advanced Product')
            ->assertSee('Add variable')
            ->assertSee('Horizontal alignment')
            ->assertSee('Data Matrix')
            ->assertSee('100% equals browser print size');

        $preview = $this->get(route('companies.label-templates.barcode-preview', $company).'?'.http_build_query([
            'product_id' => $product->id,
            'content' => '{{sku}}-{{barcode}}',
            'format' => 'datamatrix',
        ]));

        $preview->assertOk()->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->assertStringContainsString('<svg', $preview->getContent());
        $this->assertStringContainsString('ADV-100-628100077777', $preview->getContent());
    }
}
