<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->decimal('negotiated_price', 12, 2);
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, annually
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active'); // active, expired, terminated
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_agreements');
    }
};
