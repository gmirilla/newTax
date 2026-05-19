<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code', 50)->comment('Auto-generated e.g. AST-0001');
            $table->string('asset_name', 150);
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('maintenance_asset_categories')
                  ->nullOnDelete();
            $table->string('serial_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('location', 150)->nullable();
            $table->foreignId('assigned_operator_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('related_item_id')
                  ->nullable()
                  ->constrained('inventory_items')
                  ->nullOnDelete()
                  ->comment('Optional link to an inventory item representing this machine');
            $table->unsignedSmallInteger('maintenance_interval_days')
                  ->nullable()
                  ->comment('Default PM interval; overridden per-schedule');
            $table->enum('status', ['active', 'running', 'under_maintenance', 'breakdown', 'retired'])
                  ->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'asset_code']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_assets');
    }
};
