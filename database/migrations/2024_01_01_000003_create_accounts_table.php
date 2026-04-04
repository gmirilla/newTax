<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chart of Accounts - Nigerian SME friendly double-entry accounting.
     * Account types follow standard Nigerian accounting practice.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->comment('Account code e.g. 1000, 2000');
            $table->string('name');
            $table->enum('type', [
                'asset',        // 1000s - Cash, Bank, Receivables
                'liability',    // 2000s - Payables, VAT payable, WHT payable
                'equity',       // 3000s - Owner equity, retained earnings
                'revenue',      // 4000s - Sales, service income
                'expense',      // 5000s - COGS, operating expenses
                'tax',          // 6000s - CIT, VAT, PAYE, WHT accounts
            ]);
            $table->enum('sub_type', [
                'cash', 'bank', 'accounts_receivable', 'inventory', 'fixed_asset', 'other_asset',
                'accounts_payable', 'vat_payable', 'wht_payable', 'paye_payable', 'loan', 'other_liability',
                'owners_equity', 'retained_earnings',
                'sales_revenue', 'service_revenue', 'other_revenue',
                'cost_of_goods_sold', 'salaries', 'rent', 'utilities', 'transport', 'other_expense',
                'cit_payable', 'vat_control', 'wht_control', 'paye_control',
            ])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->boolean('is_system')->default(false)->comment('System accounts cannot be deleted');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
