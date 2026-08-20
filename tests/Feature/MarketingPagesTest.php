<?php

namespace Tests\Feature;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_promotes_the_free_forever_starter_plan(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Free forever for Starter')
            ->assertSee('No trial expiry')
            ->assertSee('Create your free workspace')
            ->assertSee('application/ld+json', false)
            ->assertSee('<link rel="canonical"', false);

        $schema = $this->structuredDataFrom($response->getContent());

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('SoftwareApplication', $schema['@type']);
    }

    public function test_all_public_marketing_pages_render_unique_search_content(): void
    {
        $pages = [
            'marketing.features' => 'Everything your team needs to control stock',
            'marketing.pricing' => 'Starter is free forever',
            'marketing.barcode-inventory-system' => 'Turn every scan into clearer inventory',
            'marketing.barcode-label-printing' => 'Design labels that fit your products and printer',
            'marketing.inventory-management-small-business' => 'Inventory control without enterprise complexity',
            'marketing.warehouse-inventory-management' => 'Know what is in each location',
            'marketing.stock-counting-software' => 'Make stock counts easier to review and trust',
            'marketing.inventory-management-saudi-arabia' => 'Bilingual inventory management for Saudi teams',
        ];

        foreach ($pages as $routeName => $heading) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Start free forever')
                ->assertSee('Frequently asked questions')
                ->assertSee('application/ld+json', false)
                ->assertSee('<meta name="description"', false)
                ->assertSee('<link rel="canonical"', false);
        }
    }

    public function test_landing_and_all_marketing_pages_have_complete_arabic_content(): void
    {
        $this->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('وضوح كامل للمخزون')
            ->assertSee('باقة ستارتر مجانية الآن للأبد')
            ->assertSee('أحدث حركات المخزون')
            ->assertSee('إنشاء مساحة مجانية')
            ->assertDontSee('Create products and barcodes');

        $pages = [
            'marketing.features' => 'كل ما يحتاجه فريقك للتحكم بالمخزون',
            'marketing.pricing' => 'باقة ستارتر مجانية للأبد',
            'marketing.barcode-inventory-system' => 'حوّل كل مسح باركود إلى مخزون أوضح',
            'marketing.barcode-label-printing' => 'صمّم ملصقات تناسب أصنافك وطابعتك',
            'marketing.inventory-management-small-business' => 'تحكم بالمخزون بدون تعقيد أنظمة المؤسسات',
            'marketing.warehouse-inventory-management' => 'اعرف ما في كل موقع وكيف وصل إليه',
            'marketing.stock-counting-software' => 'اجعل الجرد أسهل في المراجعة وأكثر موثوقية',
            'marketing.inventory-management-saudi-arabia' => 'إدارة مخزون ثنائية اللغة للفرق السعودية',
        ];

        foreach ($pages as $routeName => $heading) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('الأسئلة الشائعة')
                ->assertSee('ابدأ مجاناً للأبد')
                ->assertSee('<meta property="og:locale" content="ar_SA">', false)
                ->assertDontSee('Frequently asked questions');
        }

        $this->assertStringContainsString('"Tajawal"', file_get_contents(resource_path('css/tajawal.css')));
    }

    public function test_sitemap_and_robots_expose_the_public_pages_to_search_engines(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'))
            ->assertSee(route('marketing.pricing'))
            ->assertSee(route('marketing.inventory-management-saudi-arabia'));

        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /admin')
            ->assertSee(route('sitemap'));
    }

    public function test_new_starter_workspaces_are_active_without_a_trial_expiry(): void
    {
        $user = User::factory()->create();
        $company = app(CreateCompany::class)->handle($user, 'Free Forever Company');

        $this->assertSame('starter', $company->plan);
        $this->assertSame('active', $company->subscription_status);
        $this->assertNull($company->trial_ends_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredDataFrom(string $html): array
    {
        $this->assertSame(1, preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
