<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For every existing tenant, find all GL accounts with sub_type='bank'
        // and create a corresponding bank_accounts row.
        // Account 1002 (Bank Account - Current) is marked is_default = true.
        $now = now();

        $bankAccounts = DB::table('accounts')
            ->whereNull('deleted_at')
            ->where('sub_type', 'bank')
            ->where('is_active', true)
            ->orderBy('tenant_id')
            ->orderBy('code')
            ->get(['id', 'tenant_id', 'code', 'name']);

        // Collect tenant_ids we've already assigned a default to
        $defaultSet = [];

        foreach ($bankAccounts as $gl) {
            $isDefault = false;
            if (! in_array($gl->tenant_id, $defaultSet)) {
                // First bank account for this tenant (lowest code) becomes default.
                // Code 1002 (Bank Current) is always the first since we ORDER BY code.
                $isDefault = true;
                $defaultSet[] = $gl->tenant_id;
            }

            $accountType = str_contains(strtolower($gl->name), 'saving') ? 'savings' : 'current';

            DB::table('bank_accounts')->insertOrIgnore([
                'tenant_id'      => $gl->tenant_id,
                'name'           => $gl->name,
                'bank_name'      => null,
                'account_number' => null,
                'account_type'   => $accountType,
                'currency'       => 'NGN',
                'gl_account_id'  => $gl->id,
                'opening_balance'=> 0,
                'is_default'     => $isDefault,
                'is_active'      => true,
                'sort_order'     => 0,
                'notes'          => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Remove seeded rows (those without an account_number, matching our seeding pattern)
        DB::table('bank_accounts')->whereNull('account_number')->delete();
    }
};
