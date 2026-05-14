<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Merge inventory and inventory_reports into each plan's limits JSON.
     * Free gets false/false; Growth gets true/false; Business + Enterprise get true/true.
     */
    public function up(): void
    {
        $overrides = [
            'free'       => ['inventory' => false, 'inventory_reports' => false],
            'growth'     => ['inventory' => true,  'inventory_reports' => false],
            'business'   => ['inventory' => true,  'inventory_reports' => true],
            'enterprise' => ['inventory' => true,  'inventory_reports' => true],
        ];

        foreach ($overrides as $slug => $flags) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if (!$plan) continue;

            $limits = json_decode($plan->limits, true) ?? [];
            $limits = array_merge($limits, $flags);

            DB::table('plans')->where('slug', $slug)->update([
                'limits'     => json_encode($limits),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $slugs = ['free', 'growth', 'business', 'enterprise'];

        foreach ($slugs as $slug) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if (!$plan) continue;

            $limits = json_decode($plan->limits, true) ?? [];
            unset($limits['inventory'], $limits['inventory_reports']);

            DB::table('plans')->where('slug', $slug)->update([
                'limits'     => json_encode($limits),
                'updated_at' => now(),
            ]);
        }
    }
};
