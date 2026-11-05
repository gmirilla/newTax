<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('partner_code', 12)->nullable()->unique()->after('referral_code');
        });

        // Back-fill existing tenants, same pattern as referral_code back-fills elsewhere.
        DB::table('tenants')->whereNull('partner_code')->lazyById()->each(
            fn ($row) => DB::table('tenants')
                ->where('id', $row->id)
                ->update(['partner_code' => strtoupper(Str::random(8))])
        );
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('partner_code');
        });
    }
};
