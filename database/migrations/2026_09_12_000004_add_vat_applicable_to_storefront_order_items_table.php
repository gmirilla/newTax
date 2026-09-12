<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the effective VAT flag (storefront.vat_applicable AND
     * product/service.vat_applicable) at the time the order was placed, so a
     * historical order's VAT stays accurate even if settings change later.
     */
    public function up(): void
    {
        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->boolean('vat_applicable')->default(true)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_order_items', function (Blueprint $table) {
            $table->dropColumn('vat_applicable');
        });
    }
};
