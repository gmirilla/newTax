<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference');
            $table->date('expense_date');
            $table->string('category')->comment('e.g. rent, utilities, salaries, transport');
            $table->text('description');
            $table->decimal('amount', 18, 2);

            // VAT on purchases (input VAT)
            $table->boolean('vat_applicable')->default(false);
            $table->decimal('vat_amount', 18, 2)->default(0)->comment('Input VAT claimable');

            // WHT deductions from vendor
            $table->boolean('wht_applicable')->default(false);
            $table->decimal('wht_rate', 5, 2)->default(0);
            $table->decimal('wht_amount', 18, 2)->default(0)->comment('WHT deducted from vendor');

            $table->decimal('net_payable', 18, 2)->default(0)->comment('amount - wht_amount');

            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->string('receipt_path')->nullable()->comment('Uploaded receipt/invoice file');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference']);
            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
