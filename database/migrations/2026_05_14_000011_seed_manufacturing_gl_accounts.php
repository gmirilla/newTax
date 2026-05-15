<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get(['id']);

        foreach ($tenants as $tenant) {
            // 1201 — Raw Materials inventory sub-account
            $exists1201 = DB::table('accounts')
                ->where('tenant_id', $tenant->id)
                ->where('code', '1201')
                ->exists();

            if (! $exists1201) {
                DB::table('accounts')->insert([
                    'tenant_id'   => $tenant->id,
                    'code'        => '1201',
                    'name'        => 'Raw Materials Inventory',
                    'type'        => 'asset',
                    'sub_type'    => 'inventory',
                    'description' => 'Raw materials held for use in production',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // 1202 — Finished Goods inventory sub-account
            $exists1202 = DB::table('accounts')
                ->where('tenant_id', $tenant->id)
                ->where('code', '1202')
                ->exists();

            if (! $exists1202) {
                DB::table('accounts')->insert([
                    'tenant_id'   => $tenant->id,
                    'code'        => '1202',
                    'name'        => 'Finished Goods Inventory',
                    'type'        => 'asset',
                    'sub_type'    => 'inventory',
                    'description' => 'Completed goods ready for sale',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Only remove accounts that have no journal entries attached
        DB::table('accounts')
            ->whereIn('code', ['1201', '1202'])
            ->whereNotIn('id', function ($q) {
                $q->select('account_id')->from('journal_entries');
            })
            ->delete();
    }
};
