<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create a "Main Store" default location for every existing tenant,
        // then assign all existing stock movements, sales orders, and restock
        // requests to that location.
        $tenants = DB::table('tenants')->select('id')->get();

        foreach ($tenants as $tenant) {
            $locationId = DB::table('inventory_locations')->insertGetId([
                'tenant_id'  => $tenant->id,
                'name'       => 'Main Store',
                'code'       => 'MAIN',
                'is_default' => true,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('stock_movements')
                ->where('tenant_id', $tenant->id)
                ->whereNull('location_id')
                ->update(['location_id' => $locationId]);

            DB::table('sales_orders')
                ->where('tenant_id', $tenant->id)
                ->whereNull('location_id')
                ->update(['location_id' => $locationId]);

            DB::table('restock_requests')
                ->where('tenant_id', $tenant->id)
                ->whereNull('location_id')
                ->update(['location_id' => $locationId]);
        }
    }

    public function down(): void
    {
        // Remove the backfilled location references before dropping the locations
        DB::table('stock_movements')->update(['location_id' => null]);
        DB::table('sales_orders')->update(['location_id' => null]);
        DB::table('restock_requests')->update(['location_id' => null]);
        DB::table('inventory_locations')->where('code', 'MAIN')->delete();
    }
};
