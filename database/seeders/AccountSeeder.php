<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Tenant;
use App\Services\BookkeepingService;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(BookkeepingService::class);

        foreach (Tenant::all() as $tenant) {
            $service->provisionDefaultAccounts($tenant);
        }
    }
}
