<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM(
                'sale','purchase','expense','income',
                'payment','receipt','journal','tax_payment',
                'payroll','bank_transfer','opening_balance'
            ) NOT NULL");
        } else {
            // PostgreSQL — enum() creates a VARCHAR with a CHECK constraint named
            // {table}_{column}_check.  We drop and recreate that constraint.
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

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Remove opening_balance — any existing rows with that type must be
            // converted first or this will fail due to MySQL's strict ENUM check.
            DB::statement("UPDATE transactions SET `type` = 'journal' WHERE `type` = 'opening_balance'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN `type` ENUM(
                'sale','purchase','expense','income',
                'payment','receipt','journal','tax_payment',
                'payroll','bank_transfer'
            ) NOT NULL");
        } else {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (
                type IN (
                    'sale','purchase','expense','income',
                    'payment','receipt','journal','tax_payment',
                    'payroll','bank_transfer'
                )
            )");
        }
    }
};
