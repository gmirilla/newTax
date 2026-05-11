<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('description')->comment('Snapshot of item name at time of sale');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 18, 2)->default(0)->comment('Selling price at time of sale');
            $table->decimal('cost_price_at_sale', 18, 2)->default(0)->comment('Snapshot of avg_cost at time of sale — used for COGS');
            $table->decimal('subtotal', 18, 2)->default(0)->comment('quantity * unit_price');
            $table->boolean('vat_applicable')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(7.50)->comment('Nigerian VAT rate 7.5%');
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0)->comment('subtotal + vat_amount');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sale_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_order_items');
    }
};
