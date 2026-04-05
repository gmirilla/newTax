<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();

            $table->string('quote_number')->comment('Auto-generated: QUO-YYYYMM-NNNN');
            $table->string('reference')->nullable()->comment('Customer PO or reference');

            $table->date('quote_date');
            $table->date('expiry_date');

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('wht_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->boolean('vat_applicable')->default(true);
            $table->boolean('wht_applicable')->default(false);
            $table->decimal('wht_rate', 5, 2)->default(0);

            $table->enum('status', ['draft', 'sent', 'accepted', 'declined', 'expired'])->default('draft');

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('currency', 3)->default('NGN');

            // Set when the quote is accepted and converted to a real invoice
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'quote_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'quote_date']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->boolean('vat_applicable')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(7.50);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
