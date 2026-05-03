<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('paystack_plan_code_yearly')->nullable()->after('paystack_plan_code');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_cycle', 10)->default('monthly')->after('subscription_status');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('billing_cycle', 10)->default('monthly')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('paystack_plan_code_yearly');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
};
