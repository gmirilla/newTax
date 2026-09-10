<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storefront_category_id')->nullable()->constrained('storefront_categories')->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 18, 2);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_services');
    }
};
