<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_order_id')->constrained('storefront_orders')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->decimal('total', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_order_items');
    }
};
