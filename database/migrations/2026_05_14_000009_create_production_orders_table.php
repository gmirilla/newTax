<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('order_number', 30)->unique();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('finished_item_id');
            $table->decimal('quantity_planned', 15, 3);
            $table->decimal('quantity_produced', 15, 3)->nullable();
            $table->decimal('additional_cost', 15, 2)->default(0);
            $table->enum('status', ['draft', 'in_production', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('bom_id')->references('id')->on('boms')->restrictOnDelete();
            $table->foreign('finished_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('production_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('raw_material_item_id');
            $table->decimal('quantity_required', 15, 3);
            $table->decimal('quantity_consumed', 15, 3)->nullable();
            $table->decimal('unit_cost_at_production', 15, 4)->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->cascadeOnDelete();
            $table->foreign('raw_material_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_lines');
        Schema::dropIfExists('production_orders');
    }
};
