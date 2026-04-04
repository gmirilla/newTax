<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_firs_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();

            // All credential columns use text (not string) — encrypted output is longer than raw values.
            // Laravel's encrypted cast handles encryption/decryption transparently.
            $table->text('service_id');
            $table->text('api_key');
            $table->text('secret_key');
            $table->text('public_key')->nullable()->comment('Base64-decoded PEM public key from crypto_keys.txt');
            $table->text('certificate')->nullable()->comment('Base64-decoded certificate from crypto_keys.txt');

            $table->boolean('is_active')->default(true);
            $table->timestamp('credentials_set_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_firs_credentials');
    }
};
