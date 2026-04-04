<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable(); // Nigerian state
            $table->string('tin')->nullable();   // Tax Identification Number (FIRS)
            $table->string('rc_number')->nullable(); // CAC Registration Number
            $table->enum('business_type', ['sole_proprietorship', 'partnership', 'limited_liability', 'public_company'])->default('limited_liability');
            $table->enum('tax_category', ['small', 'medium', 'large'])->default('small');
            // small: turnover <= 25M NGN (0% CIT), medium: 25M-100M (20% CIT), large: >100M (30% CIT)
            $table->decimal('annual_turnover', 18, 2)->default(0);
            $table->boolean('vat_registered')->default(false); // VAT mandatory if turnover > 25M
            $table->string('vat_number')->nullable();
            $table->string('logo')->nullable();
            $table->string('currency')->default('NGN');
            $table->enum('subscription_plan', ['free', 'starter', 'pro', 'enterprise'])->default('free');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
