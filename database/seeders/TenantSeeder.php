<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $growthPlan   = Plan::where('slug', 'growth')->first();
        $businessPlan = Plan::where('slug', 'business')->first();

        // Demo company 1: Small company (VAT exempt, 0% CIT)
        $tenant1 = Tenant::create([
            'name'             => 'Adetokunbo Ventures Ltd',
            'slug'             => 'adetokunbo-ventures',
            'email'            => 'accounts@adetokunboventures.ng',
            'phone'            => '+234 803 000 0001',
            'address'          => '14 Broad Street, Lagos Island',
            'city'             => 'Lagos',
            'state'            => 'Lagos',
            'tin'              => '1234567-0001',
            'rc_number'        => 'RC-12345',
            'business_type'    => 'limited_liability',
            'annual_turnover'  => 18_000_000.00, // ₦18M - small company
            'currency'         => 'NGN',
            'is_active'        => true,
        ]);
        $tenant1->updateTaxCategory(); // derives tax_category + vat_registered from turnover, same as registration
        if ($growthPlan) {
            $tenant1->assignPlan($growthPlan, 'active', now()->addYear());
        }

        // Demo company 2: Larger company (VAT registered, 30% CIT under 2026 Finance Act rules)
        $tenant2 = Tenant::create([
            'name'             => 'Chukwuemeka & Sons Trading Co.',
            'slug'             => 'chukwuemeka-sons',
            'email'            => 'finance@chukwuemekatrading.com',
            'phone'            => '+234 812 000 0002',
            'address'          => '5 Wuse Zone 5, Plot 123',
            'city'             => 'Abuja',
            'state'            => 'FCT',
            'tin'              => '9876543-0002',
            'rc_number'        => 'RC-98765',
            'business_type'    => 'limited_liability',
            'annual_turnover'  => 65_000_000.00, // ₦65M - larger company
            'vat_number'       => 'VAT-98765-0002',
            'currency'         => 'NGN',
            'is_active'        => true,
        ]);
        $tenant2->updateTaxCategory(); // derives tax_category + vat_registered from turnover, same as registration
        if ($businessPlan) {
            $tenant2->assignPlan($businessPlan, 'active', now()->addYear());
        }
    }
}
