<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original enum check constraint only allowed 'free', 'starter', 'pro', 'enterprise'.
        // Plans are now DB-driven (plan_id → plans.slug), so subscription_plan is a legacy sync
        // column that must accept any slug string.
        DB::statement("ALTER TABLE tenants DROP CONSTRAINT IF EXISTS tenants_subscription_plan_check");
        DB::statement("ALTER TABLE tenants ALTER COLUMN subscription_plan TYPE VARCHAR(50)");
    }

    public function down(): void
    {
        // Restore the original enum constraint (data must already conform)
        DB::statement("ALTER TABLE tenants ALTER COLUMN subscription_plan TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE tenants ADD CONSTRAINT tenants_subscription_plan_check
                        CHECK (subscription_plan IN ('free', 'starter', 'pro', 'enterprise'))");
    }
};
