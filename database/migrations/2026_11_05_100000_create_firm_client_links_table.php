<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_client_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('status', ['pending_client_approval', 'pending_firm_approval', 'active', 'revoked'])
                  ->default('pending_client_approval');
            $table->foreignId('proxy_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('token', 64)->nullable()->unique(); // email-invite entry point only
            $table->timestamps();

            $table->index(['firm_tenant_id', 'status']);
            $table->index(['client_tenant_id', 'status']);
        });

        // Postgres-only guards (mirrors the referrals table's self-referral CHECK —
        // see 2026_05_25_100001_create_referrals_table.php). Skipped on SQLite
        // (test env); enforced in FirmClientLinkService at the app layer instead.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE firm_client_links ADD CONSTRAINT chk_no_self_link CHECK (firm_tenant_id != client_tenant_id)');
            DB::statement("CREATE UNIQUE INDEX uniq_active_firm_client_link ON firm_client_links (firm_tenant_id, client_tenant_id) WHERE status != 'revoked'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_client_links');
    }
};
