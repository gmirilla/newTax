<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('id');
            // subscription_status already exists — skip
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->string('paystack_customer_id')->nullable()->after('trial_ends_at');
            $table->string('paystack_subscription_code')->nullable()->after('paystack_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'trial_ends_at', 'paystack_customer_id', 'paystack_subscription_code']);
        });
    }
};
