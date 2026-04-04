<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add development_levy to cit_records (replaces education tax from 2026)
        Schema::table('cit_records', function (Blueprint $table) {
            $table->decimal('development_levy', 18, 2)->default(0)
                  ->after('education_levy')
                  ->comment('4% of assessable profit — replaces TETFund/IT Levy/NASENI (Finance Act 2025)');
        });

        // Add is_professional_firm to tenants
        // Professional firms cannot claim the small-company CIT exemption
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('is_professional_firm')->default(false)
                  ->after('tax_category')
                  ->comment('Lawyers, engineers, accountants etc. — always pay 30% CIT');
        });
    }

    public function down(): void
    {
        Schema::table('cit_records', function (Blueprint $table) {
            $table->dropColumn('development_levy');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('is_professional_firm');
        });
    }
};
