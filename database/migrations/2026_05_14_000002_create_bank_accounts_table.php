<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);           // Display label e.g. "GTBank Current"
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->enum('account_type', ['current', 'savings', 'other'])->default('current');
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('gl_account_id');   // FK to accounts.id (auto-created)
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('gl_account_id')->references('id')->on('accounts')->restrictOnDelete();

            // Prevent duplicate account numbers per tenant
            $table->unique(['tenant_id', 'account_number'], 'bank_accounts_tenant_acct_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
