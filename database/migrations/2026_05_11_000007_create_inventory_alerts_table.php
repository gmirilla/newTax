<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['low_stock', 'out_of_stock']);
            $table->decimal('stock_at_alert', 15, 3)->comment('Stock level snapshot when alert was created');
            $table->timestamp('notified_at')->nullable()->comment('When email notification was dispatched');
            $table->timestamp('seen_at')->nullable()->comment('When admin dismissed the alert');
            $table->timestamps();

            $table->index(['tenant_id', 'seen_at']);
            $table->index(['tenant_id', 'item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
