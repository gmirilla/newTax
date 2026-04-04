<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id')->comment('Company-assigned ID');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->string('state_of_residence')->nullable()->comment('Used for PAYE state allocation');
            $table->string('tin')->nullable()->comment('Personal TIN for PAYE');
            $table->string('bvn')->nullable()->comment('Bank Verification Number');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();

            // Employment details
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('job_title');
            $table->string('department')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');

            // Salary structure (in NGN)
            $table->decimal('basic_salary', 18, 2)->default(0);
            $table->decimal('housing_allowance', 18, 2)->default(0);
            $table->decimal('transport_allowance', 18, 2)->default(0);
            $table->decimal('medical_allowance', 18, 2)->default(0);
            $table->decimal('other_allowances', 18, 2)->default(0);
            $table->decimal('gross_salary', 18, 2)->default(0)->comment('Sum of all components');

            // PAYE relief items (reduce taxable income)
            $table->decimal('pension_rate', 5, 2)->default(8.00)->comment('Employee pension: 8% of basic+housing+transport');
            $table->decimal('nhf_rate', 5, 2)->default(2.50)->comment('National Housing Fund: 2.5% of basic');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->integer('pay_year');
            $table->integer('pay_month');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->decimal('total_gross', 18, 2)->default(0);
            $table->decimal('total_paye', 18, 2)->default(0);
            $table->decimal('total_pension', 18, 2)->default(0);
            $table->decimal('total_nhf', 18, 2)->default(0);
            $table->decimal('total_net', 18, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'pay_year', 'pay_month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();

            // Earnings
            $table->decimal('basic_salary', 18, 2)->default(0);
            $table->decimal('housing_allowance', 18, 2)->default(0);
            $table->decimal('transport_allowance', 18, 2)->default(0);
            $table->decimal('medical_allowance', 18, 2)->default(0);
            $table->decimal('other_allowances', 18, 2)->default(0);
            $table->decimal('overtime', 18, 2)->default(0);
            $table->decimal('bonus', 18, 2)->default(0);
            $table->decimal('gross_pay', 18, 2)->default(0);

            // Statutory deductions
            $table->decimal('pension_employee', 18, 2)->default(0)->comment('8% of basic+housing+transport');
            $table->decimal('pension_employer', 18, 2)->default(0)->comment('10% employer contribution');
            $table->decimal('nhf', 18, 2)->default(0)->comment('2.5% of basic salary');

            // PAYE computation per Nigerian tax table
            $table->decimal('consolidated_relief', 18, 2)->default(0)->comment('200,000 or 1% of gross income (whichever higher) + 20% of gross');
            $table->decimal('taxable_income', 18, 2)->default(0)->comment('gross - pension - nhf - relief');
            $table->decimal('paye_tax', 18, 2)->default(0)->comment('Computed from progressive tax table');

            // Other deductions
            $table->decimal('other_deductions', 18, 2)->default(0);
            $table->decimal('net_pay', 18, 2)->default(0)->comment('gross - all deductions');

            $table->timestamps();

            $table->index('payroll_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employees');
    }
};
