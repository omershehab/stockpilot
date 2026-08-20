<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCompany
{
    public function handle(User $owner, string $name, bool $onboardingComplete = true): Company
    {
        return DB::transaction(function () use ($owner, $name, $onboardingComplete) {
            $baseSlug = Str::slug($name) ?: 'company';
            $slug = $baseSlug;
            $suffix = 2;

            while (Company::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $company = Company::create([
                'name' => $name,
                'slug' => $slug,
                'email' => $owner->email,
                'plan' => 'starter',
                'subscription_status' => 'active',
                'trial_ends_at' => null,
                'onboarding_completed_at' => $onboardingComplete ? now() : null,
            ]);

            $company->users()->attach($owner, ['role' => 'owner', 'status' => 'active']);
            $company->locations()->create(['name' => 'Main Warehouse / المستودع الرئيسي', 'code' => 'MAIN', 'is_default' => true]);
            $company->categories()->createMany([
                ['name' => 'General', 'name_ar' => 'عام'],
                ['name' => 'Supplies', 'name_ar' => 'مستلزمات'],
            ]);
            $company->units()->createMany([
                ['name' => 'Pieces', 'name_ar' => 'قطعة', 'symbol' => 'pcs'],
                ['name' => 'Box', 'name_ar' => 'صندوق', 'symbol' => 'box'],
                ['name' => 'Kilogram', 'name_ar' => 'كجم', 'symbol' => 'kg'],
                ['name' => 'Meter', 'name_ar' => 'متر', 'symbol' => 'm'],
                ['name' => 'Liter', 'name_ar' => 'لتر', 'symbol' => 'liter'],
            ]);

            DB::table('label_templates')->insert([
                'company_id' => $company->id,
                'name' => 'Thermal 50 × 25 mm',
                'width_mm' => 50,
                'height_mm' => 25,
                'show_name' => true,
                'show_sku' => true,
                'show_price' => true,
                'is_default' => true,
                'layout' => json_encode([
                    ['id' => 'name', 'type' => 'name', 'x' => 2, 'y' => 2, 'width' => 31, 'height' => 5, 'fontSize' => 10, 'visible' => true],
                    ['id' => 'price', 'type' => 'price', 'x' => 34, 'y' => 2, 'width' => 14, 'height' => 5, 'fontSize' => 9, 'visible' => true],
                    ['id' => 'barcode', 'type' => 'barcode', 'x' => 2, 'y' => 8, 'width' => 46, 'height' => 11, 'fontSize' => 8, 'visible' => true],
                    ['id' => 'code', 'type' => 'code', 'x' => 2, 'y' => 20, 'width' => 46, 'height' => 3, 'fontSize' => 8, 'visible' => true],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $company;
        });
    }
}
