<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double-entry journal transactions.
     * Each transaction has multiple journal entries (debit/credit).
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->comment('Unique transaction reference');
            $table->date('transaction_date');
            $table->enum('type', [
                'sale', 'purchase', 'expense', 'income',
                'payment', 'receipt', 'journal', 'tax_payment',
                'payroll', 'bank_transfer',
            ]);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('NGN');
            $table->text('description')->nullable();
            $table->string('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachments')->nullable()->comment('JSON array of file paths');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['tenant_id', 'type']);
        });

        // Journal entries (debit/credit lines for each transaction)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 18, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('transactions');
    }
};
