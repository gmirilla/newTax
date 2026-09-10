<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->foreignId('storefront_service_id')->nullable()->after('inventory_item_id')
                ->constrained('storefront_services')->restrictOnDelete();
        });

        // Relaxing a column to nullable doesn't require touching its foreign key
        // on either driver — no need to drop/recreate the constraint.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE storefront_order_items MODIFY inventory_item_id BIGINT UNSIGNED NULL');
        } elseif ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE storefront_order_items ALTER COLUMN inventory_item_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE storefront_order_items MODIFY inventory_item_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE storefront_order_items ALTER COLUMN inventory_item_id SET NOT NULL');
        }

        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storefront_service_id');
        });
    }
};
