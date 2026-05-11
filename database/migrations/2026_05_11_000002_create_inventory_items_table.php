<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('inventory_categories')
                  ->nullOnDelete();
            $table->string('name', 150);
            $table->string('sku', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('piece')->comment('e.g. piece, kg, carton, litre');
            $table->decimal('selling_price', 18, 2)->default(0);
            $table->decimal('cost_price', 18, 2)->default(0)->comment('Initial/reference cost before first restock');
            $table->decimal('avg_cost', 18, 2)->default(0)->comment('Weighted average cost — updated on every restock');
            $table->decimal('current_stock', 15, 3)->default(0)->comment('Denormalised cache; source of truth is stock_movements');
            $table->decimal('restock_level', 15, 3)->default(0)->comment('Alert fires when current_stock <= this');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
