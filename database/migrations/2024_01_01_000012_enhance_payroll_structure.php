<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('utility_allowance', 18, 2)->default(0)->after('other_allowances');
            $table->boolean('nhf_enabled')->default(true)->comment('NHF 2.5% of basic — toggle per employee')->after('nhf_rate');
            $table->boolean('nhis_enabled')->default(false)->comment('HMO/NHIS monthly fixed amount')->after('nhf_enabled');
            // Fixed monthly ₦ amount — not a rate
            $table->decimal('nhis_amount', 18, 2)->default(0)->comment('Fixed monthly HMO/NHIS contribution (₦)')->after('nhis_enabled');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('utility_allowance', 18, 2)->default(0)->after('other_allowances');
            $table->decimal('nhis', 18, 2)->default(0)->comment('Fixed monthly HMO/NHIS deduction (₦)')->after('nhf');
            $table->decimal('loan_deduction', 18, 2)->default(0)->after('nhis');
            $table->decimal('advance_deduction', 18, 2)->default(0)->after('loan_deduction');
            $table->decimal('penalty_deduction', 18, 2)->default(0)->after('advance_deduction');
            $table->text('notes')->nullable()->after('net_pay');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('total_nhis', 18, 2)->default(0)->after('total_nhf');
            $table->decimal('total_employer_pension', 18, 2)->default(0)->after('total_nhis');
            $table->text('notes')->nullable()->after('total_employer_pension');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['utility_allowance', 'nhf_enabled', 'nhis_enabled', 'nhis_amount']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['utility_allowance', 'nhis', 'loan_deduction', 'advance_deduction', 'penalty_deduction', 'notes']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['total_nhis', 'total_employer_pension', 'notes']);
        });
    }
};
