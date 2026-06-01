<?php

namespace App\Jobs;

use App\Services\DatabaseExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $zipPath) {}

    public function handle(DatabaseExportService $service): void
    {
        // Remove PHP time limit — large databases will exceed the default 30s.
        set_time_limit(0);

        $service->cleanOldExports();
        $service->buildExportZip($this->zipPath);
    }
}
