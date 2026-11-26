<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            // How many times this event has been run through the handler (caps retries).
            $table->unsignedSmallInteger('attempts')->default(1)->after('status');
        });

        Schema::table('referral_credit_ledger', function (Blueprint $table) {
            // Set when a checkout debit was refunded because the checkout never completed.
            $table->timestamp('reversed_at')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', fn (Blueprint $t) => $t->dropColumn('attempts'));
        Schema::table('referral_credit_ledger', fn (Blueprint $t) => $t->dropColumn('reversed_at'));
    }
};
