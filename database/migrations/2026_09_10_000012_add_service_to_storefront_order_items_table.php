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
            $table->dropForeign(['inventory_item_id']);
        });

        DB::statement('ALTER TABLE storefront_order_items ALTER COLUMN inventory_item_id DROP NOT NULL');

        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreignId('storefront_service_id')->nullable()->after('inventory_item_id')
                ->constrained('storefront_services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storefront_service_id');
            $table->dropForeign(['inventory_item_id']);
        });

        DB::statement('ALTER TABLE storefront_order_items ALTER COLUMN inventory_item_id SET NOT NULL');

        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }
};
