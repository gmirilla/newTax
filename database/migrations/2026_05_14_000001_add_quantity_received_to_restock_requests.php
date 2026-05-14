<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->decimal('quantity_received', 15, 3)->nullable()
                  ->after('quantity_requested')
                  ->comment('Actual quantity received (may differ from requested)');
        });
    }

    public function down(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->dropColumn('quantity_received');
        });
    }
};
