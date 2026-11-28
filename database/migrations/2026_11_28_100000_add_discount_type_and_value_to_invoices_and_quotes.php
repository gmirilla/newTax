<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * discount_type is NULL for documents created before this feature. Those keep the
     * legacy maths (flat discount_amount, VAT/WHT on the pre-discount subtotal) until
     * they are edited and saved, so already-issued documents never change silently.
     */
    public function up(): void
    {
        foreach (['invoices', 'quotes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('discount_type', 10)->nullable()->after('discount_amount'); // fixed | percent
                $table->decimal('discount_value', 18, 2)->default(0)->after('discount_type');
            });
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'quotes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['discount_type', 'discount_value']);
            });
        }
    }
};
