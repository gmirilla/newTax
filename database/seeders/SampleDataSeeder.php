<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\VatService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedInvoices();
        $this->seedEmployees();
    }

    private function seedInvoices(): void
    {
        $customer = Customer::where('tenant_id', 1)->first();
        if (!$customer) return;

        // Create 3 sample invoices
        $invoices = [
            [
                'invoice_date' => now()->subDays(45),
                'due_date'     => now()->subDays(15),
                'status'       => 'paid',
                'amount'       => 500_000,
                'description'  => 'IT Consulting Services – Q4 2024',
            ],
            [
                'invoice_date' => now()->subDays(20),
                'due_date'     => now()->addDays(10),
                'status'       => 'sent',
                'amount'       => 1_200_000,
                'description'  => 'Software Development – Phase 1',
            ],
            [
                'invoice_date' => now()->subDays(5),
                'due_date'     => now()->addDays(25),
                'status'       => 'draft',
                'amount'       => 350_000,
                'description'  => 'Annual Maintenance Contract',
            ],
        ];

        foreach ($invoices as $idx => $data) {
            $invoiceNumber = 'INV-' . now()->format('Ym') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT);
            $subtotal      = $data['amount'];
            $vatAmount     = round($subtotal * VatService::VAT_RATE / 100, 2);
            $total         = $subtotal + $vatAmount;

            $invoice = Invoice::create([
                'tenant_id'      => 1,
                'customer_id'    => $customer->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => $data['invoice_date'],
                'due_date'       => $data['due_date'],
                'subtotal'       => $subtotal,
                'vat_amount'     => $vatAmount,
                'total_amount'   => $total,
                'amount_paid'    => $data['status'] === 'paid' ? $total : 0,
                'balance_due'    => $data['status'] === 'paid' ? 0 : $total,
                'vat_applicable' => true,
                'wht_applicable' => false,
                'status'         => $data['status'],
                'currency'       => 'NGN',
                'notes'          => 'Payment via bank transfer to account 0123456789 (GTB).',
                'terms'          => 'Payment due within 30 days of invoice date.',
                'created_by'     => 1,
            ]);

            InvoiceItem::create([
                'invoice_id'     => $invoice->id,
                'description'    => $data['description'],
                'quantity'       => 1,
                'unit_price'     => $subtotal,
                'subtotal'       => $subtotal,
                'vat_applicable' => true,
                'vat_rate'       => VatService::VAT_RATE,
                'vat_amount'     => $vatAmount,
                'total'          => $total,
                'sort_order'     => 1,
            ]);
        }
    }

    private function seedEmployees(): void
    {
        $employees = [
            [
                'first_name'          => 'Kemi',
                'last_name'           => 'Olatunji',
                'email'               => 'kemi@adetokunboventures.ng',
                'job_title'           => 'Senior Developer',
                'basic_salary'        => 350_000,
                'housing_allowance'   => 70_000,
                'transport_allowance' => 35_000,
                'hire_date'           => now()->subYears(2),
            ],
            [
                'first_name'          => 'Biodun',
                'last_name'           => 'Adesanya',
                'email'               => 'biodun@adetokunboventures.ng',
                'job_title'           => 'Sales Manager',
                'basic_salary'        => 280_000,
                'housing_allowance'   => 56_000,
                'transport_allowance' => 28_000,
                'hire_date'           => now()->subYears(1),
            ],
            [
                'first_name'          => 'Amaka',
                'last_name'           => 'Nwosu',
                'email'               => 'amaka@adetokunboventures.ng',
                'job_title'           => 'Accountant',
                'basic_salary'        => 220_000,
                'housing_allowance'   => 44_000,
                'transport_allowance' => 22_000,
                'hire_date'           => now()->subMonths(8),
            ],
        ];

        foreach ($employees as $idx => $data) {
            $gross = $data['basic_salary'] + $data['housing_allowance'] + $data['transport_allowance'];
            Employee::create(array_merge($data, [
                'tenant_id'        => 1,
                'employee_id'      => 'EMP-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'gross_salary'     => $gross,
                'state_of_residence' => 'Lagos',
                'employment_type'  => 'full_time',
                'pension_rate'     => 8.0,
                'nhf_rate'         => 2.5,
                'is_active'        => true,
            ]));
        }
    }
}
