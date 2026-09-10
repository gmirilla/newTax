<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfills the two GL accounts (1201 Raw Materials Inventory, 1202 Finished
     * Goods Inventory) that InventoryImportController and ProductionOrderController
     * both require but were missing from Account::DEFAULT_ACCOUNTS — every existing
     * tenant is missing them today. New tenants get them automatically via the
     * updated defaults; this migration catches everyone already provisioned.
     */
    public function up(): void
    {
        $now = now();

        $newAccounts = [
            ['code' => '1201', 'name' => 'Raw Materials Inventory'],
            ['code' => '1202', 'name' => 'Finished Goods Inventory'],
        ];

        DB::table('tenants')->orderBy('id')->select('id')->chunk(200, function ($tenants) use ($newAccounts, $now) {
            $rows = [];

            foreach ($tenants as $tenant) {
                foreach ($newAccounts as $account) {
                    $rows[] = [
                        'tenant_id'        => $tenant->id,
                        'code'             => $account['code'],
                        'name'             => $account['name'],
                        'type'             => 'asset',
                        'sub_type'         => 'inventory',
                        'opening_balance'  => 0,
                        'current_balance'  => 0,
                        'is_system'        => true,
                        'is_active'        => true,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }

            // insertOrIgnore respects the (tenant_id, code) unique constraint —
            // safe to re-run, and skips any tenant that already has these codes.
            DB::table('accounts')->insertOrIgnore($rows);
        });
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('code', ['1201', '1202'])
            ->where('is_system', true)
            ->delete();
    }
};
