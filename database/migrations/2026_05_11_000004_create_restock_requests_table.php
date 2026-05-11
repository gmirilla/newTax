<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('request_number')->comment('Auto-generated e.g. RST-2026-0001');
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('unit_cost', 18, 2)->default(0)->comment('Estimated or quoted cost per unit');
            $table->string('supplier_name', 150)->nullable();
            $table->string('supplier_invoice_no', 100)->nullable()->comment('Filled when goods are received');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'received', 'cancelled'])
                  ->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete()
                  ->comment('Supplier bill generated on receipt');
            $table->timestamps();

            $table->unique(['tenant_id', 'request_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_requests');
    }
};
