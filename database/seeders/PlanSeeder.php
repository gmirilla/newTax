<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'Free',
                'slug'          => 'free',
                'description'   => 'Get started — ideal for sole traders and freelancers.',
                'price_monthly' => 0,
                'price_yearly'  => null,
                'trial_days'    => 0,
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 1,
                'limits'        => [
                    'invoices_per_month' => 5,
                    'users'              => 1,
                    'payroll_staff'      => 0,
                    'customers'          => 20,
                    'payroll'            => false,
                    'firs'               => false,
                    'advanced_reports'   => false,
                    'inventory'          => false,
                    'inventory_reports'  => false,
                    'api_access'         => false,
                ],
            ],
            [
                'name'          => 'Growth',
                'slug'          => 'growth',
                'description'   => 'For growing businesses — unlimited invoicing and full tax compliance.',
                'price_monthly' => 8000,
                'price_yearly'  => 76800,
                'trial_days'    => 14,
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 2,
                'limits'        => [
                    'invoices_per_month' => null,
                    'users'              => 3,
                    'payroll_staff'      => 10,
                    'customers'          => null,
                    'payroll'            => true,
                    'firs'               => true,
                    'advanced_reports'   => true,
                    'inventory'          => true,
                    'inventory_reports'  => false,
                    'api_access'         => false,
                ],
            ],
            [
                'name'          => 'Business',
                'slug'          => 'business',
                'description'   => 'For established SMEs with larger teams and payroll needs.',
                'price_monthly' => 18000,
                'price_yearly'  => 172800,
                'trial_days'    => 14,
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 3,
                'limits'        => [
                    'invoices_per_month' => null,
                    'users'              => 10,
                    'payroll_staff'      => 50,
                    'customers'          => null,
                    'payroll'            => true,
                    'firs'               => true,
                    'advanced_reports'   => true,
                    'inventory'          => true,
                    'inventory_reports'  => true,
                    'api_access'         => false,
                ],
            ],
            [
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'description'   => 'Custom pricing for large organisations. Includes API access and SLA.',
                'price_monthly' => 0,
                'price_yearly'  => null,
                'trial_days'    => 30,
                'is_active'     => true,
                'is_public'     => false,
                'sort_order'    => 4,
                'limits'        => [
                    'invoices_per_month' => null,
                    'users'              => null,
                    'payroll_staff'      => null,
                    'customers'          => null,
                    'payroll'            => true,
                    'firs'               => true,
                    'advanced_reports'   => true,
                    'inventory'          => true,
                    'inventory_reports'  => true,
                    'api_access'         => true,
                ],
            ],
        ];

        foreach ($plans as $data) {
            \App\Models\Plan::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
