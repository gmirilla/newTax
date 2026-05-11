<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEFAULTS = [
        'piece', 'pair', 'kg', 'g', 'litre', 'ml',
        'carton', 'bag', 'box', 'roll', 'metre', 'set',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('tenants')->orderBy('id')->each(function ($tenant) use ($now) {
            foreach (self::DEFAULTS as $name) {
                DB::table('inventory_units')->insertOrIgnore([
                    'tenant_id'  => $tenant->id,
                    'name'       => $name,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('inventory_units')
            ->whereIn('name', self::DEFAULTS)
            ->delete();
    }
};
