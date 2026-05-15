<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE inventory_items ADD COLUMN `item_type` ENUM(
                'product','raw_material','finished_good','semi_finished'
            ) NOT NULL DEFAULT 'product' AFTER `description`");
        } else {
            DB::statement("ALTER TABLE inventory_items ADD COLUMN item_type VARCHAR(20) NOT NULL DEFAULT 'product'");
            DB::statement("ALTER TABLE inventory_items ADD CONSTRAINT inventory_items_item_type_check CHECK (
                item_type IN ('product','raw_material','finished_good','semi_finished')
            )");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE inventory_items DROP COLUMN `item_type`");
        } else {
            DB::statement("ALTER TABLE inventory_items DROP CONSTRAINT IF EXISTS inventory_items_item_type_check");
            DB::statement("ALTER TABLE inventory_items DROP COLUMN item_type");
        }
    }
};
