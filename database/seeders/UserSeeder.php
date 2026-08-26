<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $verified = now();

        $tenant1 = Tenant::where('slug', 'adetokunbo-ventures')->first();
        $tenant2 = Tenant::where('slug', 'chukwuemeka-sons')->first();

        // Tenant 1 users
        if ($tenant1) {
            User::create([
                'tenant_id'         => $tenant1->id,
                'name'              => 'Tunde Adetokunbo',
                'email'             => 'admin@adetokunboventures.ng',
                'password'          => Hash::make('password'),
                'role'              => 'admin',
                'is_active'         => true,
                'phone'             => '+234 803 000 0011',
                'email_verified_at' => $verified,
            ]);

            User::create([
                'tenant_id'         => $tenant1->id,
                'name'              => 'Ngozi Accountant',
                'email'             => 'accountant@adetokunboventures.ng',
                'password'          => Hash::make('password'),
                'role'              => 'accountant',
                'is_active'         => true,
                'email_verified_at' => $verified,
            ]);
        }

        // Tenant 2 users
        if ($tenant2) {
            User::create([
                'tenant_id'         => $tenant2->id,
                'name'              => 'Emeka Chukwuemeka',
                'email'             => 'admin@chukwuemekatrading.com',
                'password'          => Hash::make('password'),
                'role'              => 'admin',
                'is_active'         => true,
                'phone'             => '+234 812 000 0022',
                'email_verified_at' => $verified,
            ]);
        }

        // Super admin (no tenant)
        User::create([
            'tenant_id'         => null,
            'name'              => 'Platform Admin',
            'email'             => 'superadmin@accounttaxng.com',
            'password'          => Hash::make('admin123'),
            'role'              => 'admin',
            'is_active'         => true,
            'is_superadmin'     => true,
            'email_verified_at' => $verified,
        ]);
    }
}
