<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Tenant;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('referral_code', 12)->nullable()->unique()->after('paystack_subscription_code');
            $table->string('referred_by_code', 12)->nullable()->after('referral_code');
            $table->decimal('referral_credit_ngn', 10, 2)->default(0)->after('referred_by_code');
            $table->string('acquisition_source', 50)->nullable()->after('referral_credit_ngn');
            $table->string('utm_source', 100)->nullable()->after('acquisition_source');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
        });

        // Backfill referral codes for existing tenants
        Tenant::whereNull('referral_code')->each(function (Tenant $tenant) {
            $tenant->update(['referral_code' => strtoupper(Str::random(8))]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'referral_code', 'referred_by_code', 'referral_credit_ngn',
                'acquisition_source', 'utm_source', 'utm_medium', 'utm_campaign',
            ]);
        });
    }
};
