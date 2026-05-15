<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('finished_item_id');
            $table->string('name', 100);
            $table->string('version', 20)->default('1.0');
            $table->decimal('yield_qty', 15, 3)->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('finished_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'finished_item_id']);
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('raw_material_item_id');
            $table->decimal('quantity_required', 15, 3);
            $table->timestamps();

            $table->foreign('bom_id')->references('id')->on('boms')->cascadeOnDelete();
            $table->foreign('raw_material_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('boms');
    }
};
