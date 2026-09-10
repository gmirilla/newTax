<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });

        DB::statement('ALTER TABLE sale_order_items ALTER COLUMN item_id DROP NOT NULL');

        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });

        DB::statement('ALTER TABLE sale_order_items ALTER COLUMN item_id SET NOT NULL');

        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }
};
