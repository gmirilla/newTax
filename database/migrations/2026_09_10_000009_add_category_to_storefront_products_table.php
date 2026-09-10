<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->foreignId('storefront_category_id')->nullable()->after('inventory_item_id')
                ->constrained('storefront_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storefront_category_id');
        });
    }
};
