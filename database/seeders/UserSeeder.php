<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant 1 users
        User::create([
            'tenant_id' => 1,
            'name'      => 'Tunde Adetokunbo',
            'email'     => 'admin@adetokunboventures.ng',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
            'is_active' => true,
            'phone'     => '+234 803 000 0011',
        ]);

        User::create([
            'tenant_id' => 1,
            'name'      => 'Ngozi Accountant',
            'email'     => 'accountant@adetokunboventures.ng',
            'password'  => Hash::make('password'),
            'role'      => 'accountant',
            'is_active' => true,
        ]);

        // Tenant 2 users
        User::create([
            'tenant_id' => 2,
            'name'      => 'Emeka Chukwuemeka',
            'email'     => 'admin@chukwuemekatrading.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
            'is_active' => true,
            'phone'     => '+234 812 000 0022',
        ]);

        // Super admin (no tenant)
        User::create([
            'tenant_id' => null,
            'name'      => 'Platform Admin',
            'email'     => 'superadmin@naijabooks.ng',
            'password'  => Hash::make('admin123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
