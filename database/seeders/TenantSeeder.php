<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Demo company 1: Small company (VAT exempt, 0% CIT)
        Tenant::create([
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
            'tax_category'     => 'small',
            'annual_turnover'  => 18_000_000.00, // ₦18M - small company
            'vat_registered'   => false,          // below ₦25M threshold
            'currency'         => 'NGN',
            'subscription_plan'=> 'starter',
            'is_active'        => true,
        ]);

        // Demo company 2: Medium company (VAT registered, 20% CIT)
        Tenant::create([
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
            'tax_category'     => 'medium',
            'annual_turnover'  => 65_000_000.00, // ₦65M - medium company
            'vat_registered'   => true,
            'vat_number'       => 'VAT-98765-0002',
            'currency'         => 'NGN',
            'subscription_plan'=> 'pro',
            'is_active'        => true,
        ]);
    }
}
