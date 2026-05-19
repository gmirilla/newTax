<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('work_order_number')
                  ->comment('Auto-generated e.g. MWO-202605-0001');
            $table->enum('source_type', ['preventive', 'corrective'])
                  ->default('preventive');
            $table->foreignId('asset_id')
                  ->constrained('maintenance_assets')
                  ->restrictOnDelete();
            $table->foreignId('schedule_id')
                  ->nullable()
                  ->constrained('maintenance_schedules')
                  ->nullOnDelete();
            $table->unsignedBigInteger('breakdown_id')
                  ->nullable()
                  ->comment('References maintenance_breakdowns — no FK constraint (circular dep)');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 6, 2)->default(0);
            $table->decimal('actual_hours', 6, 2)->default(0)
                  ->comment('Sum of labor_log hours — denormalised for reporting');
            $table->text('remarks')->nullable();
            $table->enum('status', [
                'open', 'assigned', 'in_progress',
                'waiting_for_parts', 'completed', 'closed',
            ])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'work_order_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_orders');
    }
};
