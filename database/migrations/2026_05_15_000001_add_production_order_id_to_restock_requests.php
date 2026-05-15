<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('production_order_id')
                  ->nullable()
                  ->after('invoice_id')
                  ->comment('Set when the request was generated from a production order shortfall');

            $table->foreign('production_order_id')
                  ->references('id')
                  ->on('production_orders')
                  ->nullOnDelete();

            $table->index(['production_order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropIndex(['production_order_id']);
            $table->dropColumn('production_order_id');
        });
    }
};
