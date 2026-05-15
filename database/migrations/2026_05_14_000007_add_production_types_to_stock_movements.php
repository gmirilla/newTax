<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN `type` ENUM(
                'sale','restock','adjustment_in','adjustment_out','opening',
                'production_in','production_out'
            ) NOT NULL");
        } else {
            DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check");
            DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check CHECK (
                type IN (
                    'sale','restock','adjustment_in','adjustment_out','opening',
                    'production_in','production_out'
                )
            )");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE stock_movements SET `type` = 'adjustment_out' WHERE `type` = 'production_out'");
            DB::statement("UPDATE stock_movements SET `type` = 'adjustment_in'  WHERE `type` = 'production_in'");
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN `type` ENUM(
                'sale','restock','adjustment_in','adjustment_out','opening'
            ) NOT NULL");
        } else {
            DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check");
            DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check CHECK (
                type IN ('sale','restock','adjustment_in','adjustment_out','opening')
            )");
        }
    }
};
