<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('wht_exempt')->default(false)->after('wht_rate')
                ->comment('True for vendors not subject to Nigerian WHT (e.g. foreign vendors)');
            $table->string('wht_exempt_reason')->nullable()->after('wht_exempt')
                ->comment('Reason WHT does not apply, e.g. foreign_income, diplomatic, govt_entity');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['wht_exempt', 'wht_exempt_reason']);
        });
    }
};
