<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Set when a trial ends: monthly limits count only usage from this moment
            // (within the current month), so trial-plan usage does not burn the Free quota.
            $table->timestamp('limit_window_start')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('limit_window_start');
        });
    }
};
