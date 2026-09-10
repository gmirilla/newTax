<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Relaxing a column to nullable doesn't require touching its foreign key
     * on either driver — no need to drop/recreate the constraint.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sale_order_items MODIFY item_id BIGINT UNSIGNED NULL');
        } elseif ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE sale_order_items ALTER COLUMN item_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sale_order_items MODIFY item_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE sale_order_items ALTER COLUMN item_id SET NOT NULL');
        }
    }
};
