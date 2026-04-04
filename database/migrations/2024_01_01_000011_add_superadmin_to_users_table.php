<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tenant_id, role, is_active, phone already added by 000002_add_tenant_to_users_table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('password');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_status')->default('active')->after('subscription_expires_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'reminder_sent_at']);
        });
    }
};
