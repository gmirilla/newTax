<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique(); // PLT-YYYYMM-NNNN
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_id')->constrained('enterprise_agreements');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status', 20)->default('draft'); // draft, sent, paid, overdue, void
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable(); // bank_transfer, cheque, etc.
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_invoices');
    }
};
