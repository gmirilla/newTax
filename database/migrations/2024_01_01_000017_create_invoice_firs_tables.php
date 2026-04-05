<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. firs_status column on invoices ────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('firs_status', ['draft', 'pending', 'validating', 'signing', 'signed', 'failed'])
                  ->default('draft')
                  ->after('status')
                  ->comment('FIRS e-Invoicing pipeline state — enforced as a forward-only state machine');

            $table->boolean('is_b2c')
                  ->default(false)
                  ->after('firs_status')
                  ->comment('true = Simplified Tax Invoice (B2C / type 388); false = Tax Invoice (B2B / type 380)');
        });

        // ── 2. invoice_firs_submissions ───────────────────────────────────────
        // One record per invoice — tracks the current submission lifecycle.
        Schema::create('invoice_firs_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('irn')->nullable()->comment('Invoice Reference Number generated locally');
            $table->string('csid')->nullable()->comment('Cryptographic Stamp Identifier returned by FIRS');
            $table->text('qr_code_data')->nullable()->comment('QR code data returned by FIRS after signing');

            $table->enum('status', ['pending', 'validating', 'validated', 'signing', 'signed', 'failed'])
                  ->default('pending');

            $table->json('firs_response')->nullable()->comment('Full FIRS API response payload');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // ── 3. firs_api_logs ──────────────────────────────────────────────────
        // Immutable audit trail — no updated_at, no soft deletes.
        Schema::create('firs_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->string('endpoint', 200);
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();

            // Immutable — only created_at, no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firs_api_logs');
        Schema::dropIfExists('invoice_firs_submissions');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['firs_status', 'is_b2c']);
        });
    }
};
