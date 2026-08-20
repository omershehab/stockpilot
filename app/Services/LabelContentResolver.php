<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;

class LabelContentResolver
{
    /**
     * @return array<string, string>
     */
    public function variables(Product $product, Company $company): array
    {
        $product->loadMissing(['barcodes', 'batches', 'category']);
        $batch = $product->batches
            ->sortBy(fn ($item) => $item->expiry_date?->timestamp ?? PHP_INT_MAX)
            ->first();
        $displayName = app()->isLocale('ar') && $product->name_ar
            ? $product->name_ar
            : $product->name;
        $category = $product->category
            ? (app()->isLocale('ar') && $product->category->name_ar
                ? $product->category->name_ar
                : $product->category->name)
            : '';
        $currency = strtoupper((string) ($company->currency ?: 'SAR'));

        return [
            'name' => (string) $product->name,
            'name_ar' => (string) ($product->name_ar ?: $product->name),
            'display_name' => (string) $displayName,
            'sku' => (string) $product->sku,
            'barcode' => (string) ($product->primary_barcode?->code ?? ''),
            'price' => number_format((float) $product->price, 2, '.', ''),
            'price_with_currency' => number_format((float) $product->price, 2, '.', '').' '.$currency,
            'cost' => number_format((float) $product->cost, 2, '.', ''),
            'currency' => $currency,
            'description' => (string) ($product->description ?? ''),
            'unit' => (string) ($product->unit ?? ''),
            'category' => (string) $category,
            'batch' => (string) ($batch?->batch_number ?? ''),
            'expiry' => (string) ($batch?->expiry_date?->format('Y-m-d') ?? ''),
            'company' => (string) $company->name,
        ];
    }

    public function resolve(?string $content, Product $product, Company $company, string $fallback = ''): string
    {
        $template = trim((string) $content);
        if ($template === '') {
            $template = $fallback;
        }

        $variables = $this->variables($product, $company);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $matches): string => $variables[strtolower($matches[1])] ?? $matches[0],
            $template,
        );
    }
}
