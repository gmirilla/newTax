<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('plans')->get(['id', 'slug', 'limits']);

        foreach ($rows as $plan) {
            $limits = json_decode($plan->limits ?? '{}', true) ?? [];

            $limits['manufacturing'] = in_array($plan->slug, ['business', 'enterprise']);

            DB::table('plans')->where('id', $plan->id)->update([
                'limits' => json_encode($limits),
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('plans')->get(['id', 'limits']);

        foreach ($rows as $plan) {
            $limits = json_decode($plan->limits ?? '{}', true) ?? [];
            unset($limits['manufacturing']);
            DB::table('plans')->where('id', $plan->id)->update([
                'limits' => json_encode($limits),
            ]);
        }
    }
};
