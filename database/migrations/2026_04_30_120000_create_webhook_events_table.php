<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('source')->index();               // 'paystack'
            $table->string('event_type')->index();           // 'charge.success', etc.
            $table->string('event_id')->nullable()->index(); // Paystack event ID (idempotency key)
            $table->json('payload');
            $table->string('status')->default('processing'); // processing | processed | failed | ignored
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Compound unique: prevents replaying the same event from the same source
            $table->unique(['source', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
