<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Paystack Plan Code (e.g. PLN_xxxxx) — links this plan to a Paystack
            // recurring plan so checkout creates an auto-renewing subscription.
            // Leave null to process as a one-time monthly payment instead.
            $table->string('paystack_plan_code')->nullable()->after('trial_days');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('paystack_plan_code');
        });
    }
};
