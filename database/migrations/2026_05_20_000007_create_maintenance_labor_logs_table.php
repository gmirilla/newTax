<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_labor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')
                  ->constrained('maintenance_work_orders')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('work_date');
            $table->decimal('hours_worked', 6, 2);
            $table->decimal('hourly_rate', 18, 2)->default(0);
            $table->decimal('labor_cost', 18, 2)->default(0)
                  ->comment('hours_worked × hourly_rate');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_labor_logs');
    }
};
