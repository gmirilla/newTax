<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 30);
            $table->uuid('token')->unique();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->text('delivery_address')->nullable();

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            $table->string('status', 20)->default('pending'); // pending|accepted|rejected|cancelled
            $table->string('channel', 20)->default('web');    // web|whatsapp
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_orders');
    }
};
