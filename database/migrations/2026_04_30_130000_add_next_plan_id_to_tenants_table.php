<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Pending end-of-cycle plan change (downgrades). Applied by the nightly
            // subscription expiry job when subscription_expires_at passes.
            $table->foreignId('next_plan_id')
                ->nullable()
                ->after('plan_id')
                ->constrained('plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Plan::class, 'next_plan_id');
            $table->dropColumn('next_plan_id');
        });
    }
};
