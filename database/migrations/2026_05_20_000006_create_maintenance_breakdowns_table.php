<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('breakdown_number')
                  ->comment('Auto-generated e.g. BRK-202605-0001');
            $table->foreignId('asset_id')
                  ->constrained('maintenance_assets')
                  ->restrictOnDelete();
            $table->foreignId('work_order_id')
                  ->nullable()
                  ->constrained('maintenance_work_orders')
                  ->nullOnDelete()
                  ->comment('Set when a corrective WO is created for this breakdown');
            $table->foreignId('reported_by')->constrained('users');
            $table->text('issue_description');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamp('downtime_start');
            $table->timestamp('downtime_end')->nullable();
            $table->decimal('downtime_hours', 8, 2)->nullable()
                  ->comment('Calculated from downtime_start / downtime_end');
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])
                  ->default('open');
            $table->timestamps();

            $table->unique(['tenant_id', 'breakdown_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_breakdowns');
    }
};
