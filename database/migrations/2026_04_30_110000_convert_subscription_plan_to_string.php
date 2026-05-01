<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: drop the check constraint then retype the column
            DB::statement("ALTER TABLE tenants DROP CONSTRAINT IF EXISTS tenants_subscription_plan_check");
            DB::statement("ALTER TABLE tenants ALTER COLUMN subscription_plan TYPE VARCHAR(50)");
        } else {
            // MySQL / MariaDB: just redefine the column — no separate constraint to drop
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('subscription_plan', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE tenants ALTER COLUMN subscription_plan TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE tenants ADD CONSTRAINT tenants_subscription_plan_check
                            CHECK (subscription_plan IN ('free', 'starter', 'pro', 'enterprise'))");
        } else {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('subscription_plan', 50)->nullable()->change();
            });
        }
    }
};
