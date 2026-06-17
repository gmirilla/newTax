<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── stock_movements ───────────────────────────────────────────────────
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('item_id')
                  ->constrained('inventory_locations')
                  ->nullOnDelete();

            // Links a transfer_out to its matching transfer_in (or vice versa)
            $table->unsignedBigInteger('transfer_pair_id')->nullable()->after('location_id');
            $table->index('transfer_pair_id');
        });

        // Extend the type check constraint to include transfer types.
        // Collect all existing distinct type values first so the constraint
        // never rejects rows added by other modules (e.g. production_in/out).
        $existing = DB::table('stock_movements')->distinct()->pluck('type')->filter()->values()->toArray();
        $allTypes = array_unique(array_merge(
            $existing,
            ['sale', 'restock', 'adjustment_in', 'adjustment_out', 'opening', 'production_in', 'production_out', 'transfer_in', 'transfer_out']
        ));
        $typeList = implode("','", $allTypes);

        DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check");
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check CHECK (type IN ('{$typeList}'))");

        // ── sales_orders ──────────────────────────────────────────────────────
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('tenant_id')
                  ->constrained('inventory_locations')
                  ->nullOnDelete();
        });

        // ── restock_requests ──────────────────────────────────────────────────
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('tenant_id')
                  ->constrained('inventory_locations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restock_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['transfer_pair_id']);
            $table->dropColumn('transfer_pair_id');
            $table->dropConstrainedForeignId('location_id');
        });

        DB::statement("ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check");
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check
            CHECK (type IN ('sale','restock','adjustment_in','adjustment_out','opening','production_in','production_out'))");
    }
};
