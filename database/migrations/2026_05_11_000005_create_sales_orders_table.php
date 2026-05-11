<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->comment('Auto-generated e.g. SO-2026-0001');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete()
                  ->comment('Null for walk-in / cash sales');
            $table->string('customer_name', 150)->nullable()->comment('Free-text name for walk-in sales');
            $table->date('sale_date');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'pos', 'cheque', 'online'])
                  ->default('cash');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete()
                  ->comment('Created on order confirmation');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete()
                  ->comment('GL posting created on confirmation');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
