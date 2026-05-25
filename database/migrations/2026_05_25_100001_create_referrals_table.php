<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('referee_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('status', ['pending', 'qualified', 'rewarded'])->default('pending');
            $table->decimal('reward_ngn', 10, 2)->default(0);
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->unique('referee_tenant_id'); // one referral record per referred tenant ever
            $table->index(['referrer_tenant_id', 'status']);
        });

        // Self-referral guard: referrer and referee must be different tenants
        DB::statement('ALTER TABLE referrals ADD CONSTRAINT chk_no_self_referral CHECK (referrer_tenant_id != referee_tenant_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
