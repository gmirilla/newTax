<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Plan::whereIn('slug', ['business', 'enterprise'])->each(function (Plan $plan) {
            $limits = $plan->limits ?? [];
            $limits['maintenance'] = true;
            $plan->update(['limits' => $limits]);
        });
    }

    public function down(): void
    {
        Plan::all()->each(function (Plan $plan) {
            $limits = $plan->limits ?? [];
            unset($limits['maintenance']);
            $plan->update(['limits' => $limits]);
        });
    }
};
