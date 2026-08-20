<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private const MARKETING_ROUTES = [
        'home',
        'marketing.features',
        'marketing.pricing',
        'marketing.barcode-inventory-system',
        'marketing.barcode-label-printing',
        'marketing.inventory-management-small-business',
        'marketing.warehouse-inventory-management',
        'marketing.stock-counting-software',
        'marketing.inventory-management-saudi-arabia',
    ];

    public function sitemap(): Response
    {
        $urls = collect(self::MARKETING_ROUTES)->map(fn (string $name, int $index) => [
            'location' => route($name),
            'priority' => $index === 0 ? '1.0' : '0.8',
            'change_frequency' => in_array($name, ['home', 'marketing.pricing'], true) ? 'weekly' : 'monthly',
        ]);

        return response()
            ->view('marketing.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /app\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
