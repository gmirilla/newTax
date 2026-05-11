<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * For inventory items that already existed before the opening-stock GL fix,
 * their opening stock movement (type = 'opening') has no corresponding journal
 * entry.  This migration backfills the gap by updating the opening_balance on
 * accounts 1200 (Inventory) and 3001 (Owner's Equity) per tenant.
 *
 * We touch opening_balance — not the journal — so it is a one-time adjustment
 * that won't interfere with future double-entry postings.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sum opening stock values per tenant
        $openingValues = DB::table('stock_movements')
            ->select('tenant_id', DB::raw('SUM(quantity * unit_cost) as total_value'))
            ->where('type', 'opening')
            // Exclude items whose opening was already journalised (reference starts with OPEN-INV-)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('transactions')
                  ->where('transactions.type', 'opening_balance')
                  ->where('transactions.tenant_id', DB::raw('stock_movements.tenant_id'))
                  ->where('transactions.reference', 'like', 'OPEN-INV-%');
            })
            ->groupBy('tenant_id')
            ->get();

        foreach ($openingValues as $row) {
            $value = round((float) $row->total_value, 2);
            if ($value <= 0) {
                continue;
            }

            // Increment opening_balance and current_balance on account 1200 (Inventory — asset)
            DB::table('accounts')
                ->where('tenant_id', $row->tenant_id)
                ->where('code', '1200')
                ->update([
                    'opening_balance' => DB::raw("opening_balance + {$value}"),
                    'current_balance' => DB::raw("current_balance + {$value}"),
                    'updated_at'      => now(),
                ]);

            // Increment opening_balance and current_balance on account 3001 (Owner's Equity — equity)
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
        // Reversal: subtract what was added
        $openingValues = DB::table('stock_movements')
            ->select('tenant_id', DB::raw('SUM(quantity * unit_cost) as total_value'))
            ->where('type', 'opening')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('transactions')
                  ->where('transactions.type', 'opening_balance')
                  ->where('transactions.tenant_id', DB::raw('stock_movements.tenant_id'))
                  ->where('transactions.reference', 'like', 'OPEN-INV-%');
            })
            ->groupBy('tenant_id')
            ->get();

        foreach ($openingValues as $row) {
            $value = round((float) $row->total_value, 2);
            if ($value <= 0) {
                continue;
            }

            foreach (['1200', '3001'] as $code) {
                DB::table('accounts')
                    ->where('tenant_id', $row->tenant_id)
                    ->where('code', $code)
                    ->update([
                        'opening_balance' => DB::raw("opening_balance - {$value}"),
                        'current_balance' => DB::raw("current_balance - {$value}"),
                        'updated_at'      => now(),
                    ]);
            }
        }
    }
};
