<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature   = 'invoices:mark-overdue';
    protected $description = 'Mark all past-due invoices as overdue';

    public function __construct(private readonly InvoiceService $invoiceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $total = 0;

        foreach (Tenant::where('is_active', true)->get() as $tenant) {
            $count  = $this->invoiceService->markOverdueInvoices($tenant);
            $total += $count;
        }

        $this->info("✅ Marked {$total} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
