<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')
                  ->constrained('maintenance_work_orders')
                  ->cascadeOnDelete();
            $table->foreignId('inventory_item_id')
                  ->constrained('inventory_items')
                  ->restrictOnDelete();
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('quantity_used', 15, 3)->default(0)
                  ->comment('Actual quantity consumed — confirmed when WO is closed');
            $table->decimal('unit_cost', 18, 2)->default(0)
                  ->comment('Snapshot of avg_cost at time of addition');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'work_order_id']);
            $table->index(['tenant_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_order_parts');
    }
};
