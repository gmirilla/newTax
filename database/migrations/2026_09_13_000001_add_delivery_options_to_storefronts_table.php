<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->boolean('pickup_enabled')->default(true)->after('vat_applicable');
            $table->boolean('delivery_enabled')->default(true)->after('pickup_enabled');
            $table->text('pickup_address')->nullable()->after('delivery_enabled');
            $table->json('delivery_states')->nullable()->after('pickup_address');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn(['pickup_enabled', 'delivery_enabled', 'pickup_address', 'delivery_states']);
        });
    }
};
