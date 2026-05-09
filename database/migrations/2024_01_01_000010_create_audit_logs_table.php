<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for compliance and NRS audit readiness.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->comment('created, updated, deleted, login, export, tax_filed');
            $table->string('auditable_type')->comment('Model class');
            $table->unsignedBigInteger('auditable_id')->comment('Model ID');
            $table->json('old_values')->nullable()->comment('Before state');
            $table->json('new_values')->nullable()->comment('After state');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('tags')->nullable()->comment('e.g. tax, invoice, payroll');
            $table->timestamps();

            $table->index(['tenant_id', 'event']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('tax_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('reminder_type', ['vat_monthly', 'wht_monthly', 'cit_annual', 'paye_monthly', 'annual_returns']);
            $table->date('due_date');
            $table->date('reminder_date');
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->integer('tax_year')->nullable();
            $table->integer('tax_month')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'due_date']);
            $table->index(['is_sent', 'reminder_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_reminders');
        Schema::dropIfExists('audit_logs');
    }
};
