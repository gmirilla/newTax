<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bank accounts created with an opening balance only set opening_balance on the
 * asset GL account. The corresponding credit to Owner's Equity (3001) was never
 * posted, causing the balance sheet to be out of balance by that amount.
 *
 * This migration backfills the equity side for all existing bank accounts that
 * have opening_balance > 0 on their GL account, following the same pattern used
 * by the inventory opening stock backfill (2026_05_11_140000).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sum opening_balance per tenant across all bank GL accounts (sub_type = 'bank')
        // that are NOT system accounts (system bank accounts 1002/1003 have opening_balance = 0
        // by default, so this safely targets only user-created bank accounts with real balances).
        $rows = DB::table('accounts')
            ->select('tenant_id', DB::raw('SUM(opening_balance) as total'))
            ->where('type', 'asset')
            ->where('sub_type', 'bank')
            ->where('is_system', false)
            ->where('opening_balance', '>', 0)
            ->groupBy('tenant_id')
            ->get();

        foreach ($rows as $row) {
            $value = round((float) $row->total, 2);
            if ($value <= 0) {
                continue;
            }

            DB::table('accounts')
                ->where('tenant_id', $row->tenant_id)
                ->where('code', '3001')
                ->update([
                    'opening_balance' => DB::raw("opening_balance + {$value}"),
                    'current_balance' => DB::raw("current_balance + {$value}"),
                    'updated_at'      => now(),
                ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('accounts')
            ->select('tenant_id', DB::raw('SUM(opening_balance) as total'))
            ->where('type', 'asset')
            ->where('sub_type', 'bank')
            ->where('is_system', false)
            ->where('opening_balance', '>', 0)
            ->groupBy('tenant_id')
            ->get();

        foreach ($rows as $row) {
            $value = round((float) $row->total, 2);
            if ($value <= 0) {
                continue;
            }

            DB::table('accounts')
                ->where('tenant_id', $row->tenant_id)
                ->where('code', '3001')
                ->update([
                    'opening_balance' => DB::raw("opening_balance - {$value}"),
                    'current_balance' => DB::raw("current_balance - {$value}"),
                    'updated_at'      => now(),
                ]);
        }
    }
};
