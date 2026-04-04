<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nigerian Tax Records - tracks VAT, WHT, CIT, PAYE obligations and filings.
     */
    public function up(): void
    {
        // VAT Returns (monthly - due by 21st of following month)
        Schema::create('vat_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->integer('tax_year');
            $table->integer('tax_month'); // 1-12
            $table->date('period_start');
            $table->date('period_end');

            // Output VAT (from sales/invoices)
            $table->decimal('output_vat', 18, 2)->default(0)->comment('VAT collected on sales');
            // Input VAT (from purchases/expenses)
            $table->decimal('input_vat', 18, 2)->default(0)->comment('VAT paid on purchases');
            // Net VAT = Output - Input
            $table->decimal('net_vat_payable', 18, 2)->default(0)->comment('Positive = pay to FIRS, Negative = credit');

            $table->date('due_date')->comment('21st of the following month');
            $table->date('filed_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->string('filing_reference')->nullable()->comment('FIRS TaxPro-Max filing ref');

            $table->enum('status', ['pending', 'filed', 'paid', 'overdue', 'nil_return'])->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'tax_year', 'tax_month']);
            $table->index(['tenant_id', 'status']);
        });

        // WHT Records (deductions made from vendors/contractors)
        Schema::create('wht_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->date('deduction_date');
            $table->decimal('gross_amount', 18, 2)->comment('Gross payment before WHT');
            $table->enum('transaction_type', ['services', 'contracts', 'rent', 'dividends', 'interest', 'royalties', 'technical_fees'])
                  ->default('services');
            // Nigerian WHT rates:
            // Services (individuals): 10%, Services (companies): 5%
            // Contracts: 5%, Rent: 10%, Dividends: 10%, Interest: 10%
            $table->decimal('wht_rate', 5, 2)->comment('WHT rate percentage');
            $table->decimal('wht_amount', 18, 2)->comment('wht_rate * gross_amount / 100');
            $table->decimal('net_payment', 18, 2)->comment('gross_amount - wht_amount');

            $table->boolean('is_company')->default(true)->comment('Company vs individual different rates');
            $table->string('vendor_tin')->nullable();
            $table->string('credit_note_number')->nullable()->comment('WHT credit note issued to vendor');

            $table->integer('tax_month');
            $table->integer('tax_year');
            $table->enum('filing_status', ['pending', 'filed', 'remitted'])->default('pending');
            $table->date('remittance_date')->nullable();
            $table->string('remittance_reference')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'deduction_date']);
            $table->index(['tenant_id', 'filing_status']);
        });

        // CIT (Company Income Tax) - Annual
        Schema::create('cit_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->integer('tax_year');

            $table->decimal('gross_profit', 18, 2)->default(0);
            $table->decimal('allowable_deductions', 18, 2)->default(0);
            $table->decimal('taxable_profit', 18, 2)->default(0);

            // CIT rates: Small (≤25M NGN) = 0%, Medium (25M-100M) = 20%, Large (>100M) = 30%
            $table->decimal('annual_turnover', 18, 2)->default(0);
            $table->enum('company_size', ['small', 'medium', 'large'])->default('small');
            $table->decimal('cit_rate', 5, 2)->default(0)->comment('0%, 20%, or 30%');
            $table->decimal('cit_amount', 18, 2)->default(0);
            $table->decimal('education_levy', 18, 2)->default(0)->comment('2% of CIT for medium/large');
            $table->decimal('total_tax_due', 18, 2)->default(0);
            $table->decimal('minimum_tax', 18, 2)->default(0)->comment('0.5% of gross turnover if CIT is less');

            $table->date('due_date')->nullable()->comment('6 months after year end');
            $table->date('filed_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->string('filing_reference')->nullable();

            $table->enum('status', ['pending', 'filed', 'paid', 'exempt', 'overdue'])->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'tax_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cit_records');
        Schema::dropIfExists('wht_records');
        Schema::dropIfExists('vat_returns');
    }
};
