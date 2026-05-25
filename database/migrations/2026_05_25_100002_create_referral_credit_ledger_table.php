<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->foreignId('subscription_payment_id')->nullable()->constrained('subscription_payments')->nullOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount_ngn', 10, 2);
            $table->string('description', 255);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_credit_ledger');
    }
};
