<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')
                  ->constrained('maintenance_assets')
                  ->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('maintenance_type', 50)->default('general')
                  ->comment('general, lubrication, inspection, calibration, overhaul');
            $table->enum('frequency_type', ['daily', 'weekly', 'monthly', 'custom_interval'])
                  ->default('monthly');
            $table->unsignedSmallInteger('frequency_days')
                  ->default(30)
                  ->comment('Interval in days: daily=1, weekly=7, monthly=30, custom=user-defined');
            $table->date('next_due_date');
            $table->decimal('estimated_hours', 6, 2)->default(1);
            $table->json('checklist')->nullable()->comment('Array of task strings');
            $table->foreignId('assigned_technician_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable()
                  ->comment('Prevents duplicate WO generation on same day');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'next_due_date']);
            $table->index(['tenant_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
