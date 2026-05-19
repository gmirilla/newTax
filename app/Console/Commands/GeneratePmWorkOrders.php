<?php

namespace App\Console\Commands;

use App\Services\MaintenanceService;
use Illuminate\Console\Command;

class GeneratePmWorkOrders extends Command
{
    protected $signature   = 'maintenance:generate-pm-work-orders';
    protected $description = 'Generate preventive maintenance work orders for due/overdue schedules';

    public function handle(MaintenanceService $service): int
    {
        $count = $service->generateDuePmWorkOrders();

        $this->info("Generated {$count} preventive maintenance work order(s).");
        return self::SUCCESS;
    }
}
