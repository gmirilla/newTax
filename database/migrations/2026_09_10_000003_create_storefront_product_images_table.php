<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storefront_product_id')->constrained('storefront_products')->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['storefront_product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_product_images');
    }
};
