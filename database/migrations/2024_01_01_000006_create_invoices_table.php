<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();

            // Invoice identification
            $table->string('invoice_number')->comment('Auto-generated: INV-YYYYMM-NNNN');
            $table->string('reference')->nullable()->comment('Customer PO or reference number');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');

            // Financial figures (all in NGN)
            $table->decimal('subtotal', 18, 2)->default(0)->comment('Sum of line items before tax');
            $table->decimal('vat_amount', 18, 2)->default(0)->comment('7.5% VAT on taxable items');
            $table->decimal('wht_amount', 18, 2)->default(0)->comment('WHT deducted at source by customer');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0)->comment('subtotal + VAT - WHT - discount');
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);

            // Tax flags
            $table->boolean('vat_applicable')->default(true);
            $table->boolean('wht_applicable')->default(false);
            $table->decimal('wht_rate', 5, 2)->default(0)->comment('WHT rate applied e.g. 5, 10');

            // Status
            $table->enum('status', ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled', 'void'])
                  ->default('draft');

            // Business metadata
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->string('qr_code')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'invoice_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0)->comment('quantity * unit_price');
            $table->boolean('vat_applicable')->default(true);
            $table->decimal('vat_rate', 5, 2)->default(7.50)->comment('Nigerian VAT rate = 7.5%');
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0)->comment('subtotal + vat_amount');
            $table->string('account_code')->nullable()->comment('Revenue account for this line');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->enum('method', ['cash', 'bank_transfer', 'cheque', 'pos', 'online'])->default('bank_transfer');
            $table->string('reference')->nullable()->comment('Bank teller/transaction ref');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
