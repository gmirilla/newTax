<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')
                  ->unique()
                  ->constrained('maintenance_work_orders')
                  ->cascadeOnDelete();
            $table->foreignId('asset_id')
                  ->constrained('maintenance_assets')
                  ->restrictOnDelete();
            $table->decimal('labor_cost', 18, 2)->default(0);
            $table->decimal('parts_cost', 18, 2)->default(0);
            $table->decimal('external_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->foreignId('transaction_id')
                  ->nullable()
                  ->constrained('transactions')
                  ->nullOnDelete()
                  ->comment('GL transaction posted when WO is closed');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_costs');
    }
};
