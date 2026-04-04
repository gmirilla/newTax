<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class CustomerVendorSeeder extends Seeder
{
    public function run(): void
    {
        // Customers for Tenant 1
        $customers = [
            ['name' => 'Nigerian Breweries Plc',     'email' => 'ap@nb.ng',     'tin' => '1111111-0001', 'city' => 'Lagos'],
            ['name' => 'Dangote Group Ltd',           'email' => 'ap@dangote.ng', 'tin' => '2222222-0002', 'city' => 'Lagos'],
            ['name' => 'NNPC Retail Limited',         'email' => 'finance@nnpc-retail.ng', 'city' => 'Abuja'],
            ['name' => 'First Bank of Nigeria',       'email' => 'payables@firstbank.com.ng', 'tin' => '3333333-0003'],
            ['name' => 'Covenant University',         'email' => 'finance@covenantuniversity.edu.ng'],
        ];

        foreach ($customers as $data) {
            Customer::create(array_merge($data, [
                'tenant_id'  => 1,
                'is_company' => true,
                'is_active'  => true,
                'state'      => 'Lagos',
                'phone'      => '+234 ' . rand(800, 900) . ' ' . rand(1000000, 9999999),
            ]));
        }

        // Vendors for Tenant 1
        $vendors = [
            ['name' => 'Office Solutions Ltd',    'vendor_type' => 'services', 'wht_rate' => 5.0,  'tin' => '4444444-0001'],
            ['name' => 'Eko Hotels & Suites',     'vendor_type' => 'rent',     'wht_rate' => 10.0, 'tin' => '5555555-0002'],
            ['name' => 'MTN Business Solutions',  'vendor_type' => 'services', 'wht_rate' => 5.0,  'tin' => '6666666-0003'],
            ['name' => 'Total Nigeria Plc',       'vendor_type' => 'goods',    'wht_rate' => 5.0],
            ['name' => 'Freelance Developer',     'vendor_type' => 'services', 'wht_rate' => 10.0], // individual
        ];

        foreach ($vendors as $data) {
            Vendor::create(array_merge($data, [
                'tenant_id' => 1,
                'is_active' => true,
                'city'      => 'Lagos',
                'state'     => 'Lagos',
            ]));
        }
    }
}
