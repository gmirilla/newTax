<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
            AccountSeeder::class,
            CustomerVendorSeeder::class,
            SampleDataSeeder::class,
        ]);
    }
}
