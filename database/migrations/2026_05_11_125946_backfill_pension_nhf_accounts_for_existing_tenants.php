<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $newAccounts = [
        ['code' => '2301', 'name' => 'Pension Contributions Payable', 'type' => 'liability', 'sub_type' => 'other_liability'],
        ['code' => '2302', 'name' => 'NHF Contributions Payable',     'type' => 'liability', 'sub_type' => 'other_liability'],
    ];

    public function up(): void
    {
        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($this->newAccounts as $account) {
                $exists = DB::table('accounts')
                    ->where('tenant_id', $tenantId)
                    ->where('code', $account['code'])
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $exists) {
                    DB::table('accounts')->insert(array_merge($account, [
                        'tenant_id'       => $tenantId,
                        'opening_balance' => 0,
                        'current_balance' => 0,
                        'is_system'       => true,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('code', ['2301', '2302'])
            ->where('is_system', true)
            ->delete();
    }
};
