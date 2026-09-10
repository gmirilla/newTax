<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SalesOrderController::reverseConfirmedOrder() posts a 'reversal'-typed
     * transaction when cancelling a confirmed sales order, but the type check
     * constraint never included it — cancelling any confirmed sales order
     * fails with a check violation. Add the missing type.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM(
                'sale','purchase','expense','income',
                'payment','receipt','journal','tax_payment',
                'payroll','bank_transfer','opening_balance','reversal'
            ) NOT NULL");
        } elseif ($driver === 'sqlite') {
            // SQLite stores VARCHAR and does not support ADD/DROP CONSTRAINT — no-op
        } else {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (
                type IN (
                    'sale','purchase','expense','income',
                    'payment','receipt','journal','tax_payment',
                    'payroll','bank_transfer','opening_balance','reversal'
                )
            )");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE transactions SET `type` = 'journal' WHERE `type` = 'reversal'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM(
                'sale','purchase','expense','income',
                'payment','receipt','journal','tax_payment',
                'payroll','bank_transfer','opening_balance'
            ) NOT NULL");
        } elseif ($driver === 'sqlite') {
            // no-op
        } else {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (
                type IN (
                    'sale','purchase','expense','income',
                    'payment','receipt','journal','tax_payment',
                    'payroll','bank_transfer','opening_balance'
                )
            )");
        }
    }
};
