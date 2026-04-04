<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NTA 2025 personal tax reliefs (effective 1 January 2026).
     * These reduce an employee's annual taxable income for PAYE computation.
     *
     *   1. Home loan interest   — fully deductible, no cap
     *   2. Life insurance       — fully deductible, no cap
     *   3. Rent relief          — 20% of annual rent, capped at ₦500,000 p.a.
     */
    public function up(): void
    {
        // Employee table — store annual relief amounts declared by the employee
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('home_loan_interest', 18, 2)->default(0)
                  ->after('nhis_amount')
                  ->comment('Annual interest on owner-occupied residential home loan — fully deductible');
            $table->decimal('life_insurance_premium', 18, 2)->default(0)
                  ->after('home_loan_interest')
                  ->comment('Annual life / annuity insurance premium — fully deductible');
            $table->decimal('annual_rent', 18, 2)->default(0)
                  ->after('life_insurance_premium')
                  ->comment('Annual rent paid — relief = min(20% × rent, ₦500,000)');
        });

        // Payroll items — store the monthly relief amounts used in each PAYE run
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('home_loan_relief', 18, 2)->default(0)
                  ->after('nhis')
                  ->comment('Monthly home loan interest relief applied in PAYE (annual ÷ 12)');
            $table->decimal('life_insurance_relief', 18, 2)->default(0)
                  ->after('home_loan_relief')
                  ->comment('Monthly life insurance relief applied in PAYE (annual ÷ 12)');
            $table->decimal('rent_relief', 18, 2)->default(0)
                  ->after('life_insurance_relief')
                  ->comment('Monthly rent relief applied in PAYE (annual relief ÷ 12)');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['home_loan_interest', 'life_insurance_premium', 'annual_rent']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['home_loan_relief', 'life_insurance_relief', 'rent_relief']);
        });
    }
};
