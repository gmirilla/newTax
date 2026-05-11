<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only ledger — no updated_at column; rows are never modified after insert
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['sale', 'restock', 'adjustment_in', 'adjustment_out', 'opening']);
            $table->decimal('quantity', 15, 3)->comment('Always positive; direction implied by type');
            $table->decimal('unit_cost', 18, 2)->default(0)->comment('Cost per unit at time of movement');
            $table->decimal('running_balance', 15, 3)->default(0)->comment('Stock level after this movement');
            $table->string('reference_type')->nullable()->comment('Polymorphic: SalesOrder or RestockRequest');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'item_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
