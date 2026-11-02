<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_payment_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique()->default('paystack');

            // Encrypted columns use text — ciphertext is longer than the raw value.
            $table->text('secret_key')->nullable();
            $table->text('public_key')->nullable();

            $table->timestamp('credentials_set_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payment_credentials');
    }
};
