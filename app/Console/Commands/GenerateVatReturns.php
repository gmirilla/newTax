<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\VatService;
use Illuminate\Console\Command;

class GenerateVatReturns extends Command
{
    protected $signature   = 'tax:generate-vat-returns {--year= : Tax year} {--month= : Tax month}';
    protected $description = 'Auto-generate VAT return records for all VAT-registered tenants';

    public function __construct(private readonly VatService $vatService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $year  = (int)($this->option('year')  ?? now()->subMonth()->year);
        $month = (int)($this->option('month') ?? now()->subMonth()->month);

        $this->info("Generating VAT returns for {$month}/{$year}...");

        $tenants = Tenant::where('is_active', true)
            ->where('vat_registered', true)
            ->get();

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            $return = $this->vatService->createOrUpdateReturn($tenant, $year, $month);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Generated {$tenants->count()} VAT returns for {$month}/{$year}.");

        return self::SUCCESS;
    }
}
