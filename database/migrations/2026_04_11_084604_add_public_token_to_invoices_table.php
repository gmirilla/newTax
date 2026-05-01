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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('id');
        });

        // Back-fill existing invoices
        DB::table('invoices')->whereNull('public_token')->lazyById()->each(
            fn($row) => DB::table('invoices')
                ->where('id', $row->id)
                ->update(['public_token' => (string) Str::uuid()])
        );
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
