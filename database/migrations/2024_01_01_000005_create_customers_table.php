<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('tin')->nullable()->comment('Customer TIN for WHT deductions');
            $table->string('rc_number')->nullable();
            $table->boolean('is_company')->default(true);
            $table->boolean('is_active')->default(true);
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('tin')->nullable()->comment('Vendor TIN - required for WHT deductions');
            $table->string('rc_number')->nullable();
            $table->enum('vendor_type', ['goods', 'services', 'rent', 'mixed'])->default('services');
            // WHT rate depends on vendor_type:
            // services: 5%, contracts: 5%, rent: 10%, dividends: 10%
            $table->decimal('wht_rate', 5, 2)->default(5.00)->comment('Withholding tax rate %');
            $table->boolean('is_active')->default(true);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
    }
};
