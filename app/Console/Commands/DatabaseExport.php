<?php

namespace App\Console\Commands;

use App\Services\DatabaseExportService;
use Illuminate\Console\Command;

class DatabaseExport extends Command
{
    protected $signature = 'db:export
                            {--output= : Custom output path for the ZIP file}';

    protected $description = 'Export the full database (tables + uploaded files) to a ZIP for migration or backup';

    public function handle(DatabaseExportService $service): int
    {
        set_time_limit(0);

        $outputPath = $this->option('output')
            ?? $service->getExportDirectory() . DIRECTORY_SEPARATOR . 'naijabooks-export-' . now()->format('Y-m-d-His') . '.zip';

        $this->info("Starting export → {$outputPath}");
        $this->newLine();

        $service->cleanOldExports();

        $result = $service->buildExportZip($outputPath, function (string $step, int $count) {
            $this->line("  ✓ {$step} ({$count} rows)");
        });

        $this->newLine();

        $sizeMb = round(filesize($outputPath) / 1024 / 1024, 2);

        $this->info("Export complete.");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tables exported', count($result['tables'])],
                ['Total rows',      array_sum($result['row_counts'])],
                ['Files exported',  $result['files']['total_files']],
                ['ZIP size',        "{$sizeMb} MB"],
                ['Output path',     $outputPath],
            ]
        );

        return self::SUCCESS;
    }
}
