<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class DatabaseImport extends Command
{
    protected $signature = 'db:import
                            {file         : Path to the NaijaBooks export ZIP file}
                            {--dry-run    : Validate and report without modifying the database}
                            {--skip-files : Import database tables only; skip uploaded files}
                            {--force      : Required when APP_ENV is not "local"}';

    protected $description = 'Import a NaijaBooks export ZIP onto a fresh database (use after php artisan migrate)';

    public function handle(): int
    {
        set_time_limit(0);

        // ── Safety guard ──────────────────────────────────────────────────────
        if (app()->environment() !== 'local' && !$this->option('force')) {
            $this->error('This command refuses to run on non-local environments without --force.');
            $this->line('Run: php artisan db:import {file} --force');
            return self::FAILURE;
        }

        $zipPath = $this->argument('file');

        if (!file_exists($zipPath)) {
            $this->error("File not found: {$zipPath}");
            return self::FAILURE;
        }

        // ── Open ZIP ──────────────────────────────────────────────────────────
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            $this->error("Could not open ZIP file: {$zipPath}");
            return self::FAILURE;
        }

        // ── Validate metadata ─────────────────────────────────────────────────
        $metadataJson = $zip->getFromName('metadata.json');

        if ($metadataJson === false) {
            $this->error('Invalid export file: metadata.json not found.');
            $zip->close();
            return self::FAILURE;
        }

        $metadata = json_decode($metadataJson, true);

        if (!isset($metadata['tables'], $metadata['row_counts'], $metadata['exported_at'])) {
            $this->error('Invalid export file: metadata.json is malformed.');
            $zip->close();
            return self::FAILURE;
        }

        $tables    = $metadata['tables'];
        $rowCounts = $metadata['row_counts'];
        $totalRows = array_sum($rowCounts);

        // ── Report ────────────────────────────────────────────────────────────
        $this->info("Export details:");
        $this->table(
            ['Key', 'Value'],
            [
                ['Exported at',   $metadata['exported_at']],
                ['App version',   $metadata['app_version'] ?? 'unknown'],
                ['Tables',        count($tables)],
                ['Total rows',    number_format($totalRows)],
                ['Files',         $metadata['files']['total_files'] ?? 0],
                ['Files size',    $this->formatBytes($metadata['files']['total_file_size_bytes'] ?? 0)],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('[dry-run] No changes made.');
            $zip->close();
            return self::SUCCESS;
        }

        // ── Confirm ───────────────────────────────────────────────────────────
        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('This will TRUNCATE all tables and replace all data on this database.');
            if (!$this->confirm('Continue?')) {
                $this->line('Aborted.');
                $zip->close();
                return self::SUCCESS;
            }
        }

        // ── Import tables ─────────────────────────────────────────────────────
        $this->newLine();
        $this->info('Importing tables…');

        // Disable FK constraint triggers for the session so tables can be
        // truncated and re-inserted in any order without FK violations.
        DB::statement('SET session_replication_role = replica');

        try {
            DB::transaction(function () use ($zip, $tables, $rowCounts) {
                foreach ($tables as $table) {
                    // Skip tables that don't exist on the target schema yet
                    if (!Schema::hasTable($table)) {
                        $this->warn("  Skipped (table not found): {$table}");
                        continue;
                    }

                    DB::table($table)->truncate();

                    $json = $zip->getFromName("tables/{$table}.json");

                    if ($json === false || $json === '[]' || $json === '') {
                        $this->line("  — {$table} (empty)");
                        continue;
                    }

                    $rows = json_decode($json, true);

                    if (empty($rows)) {
                        $this->line("  — {$table} (empty)");
                        continue;
                    }

                    // Insert in chunks to keep memory usage bounded
                    foreach (array_chunk($rows, 500) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }

                    $this->line("  ✓ {$table} (" . number_format(count($rows)) . ' rows)');
                }
            });
        } catch (\Throwable $e) {
            DB::statement('SET session_replication_role = DEFAULT');
            $this->error('Import failed: ' . $e->getMessage());
            $zip->close();
            return self::FAILURE;
        }

        DB::statement('SET session_replication_role = DEFAULT');

        // ── Fix sequences ─────────────────────────────────────────────────────
        $this->newLine();
        $this->info('Resetting PostgreSQL sequences…');
        $this->resetSequences($tables);

        // ── Extract files ─────────────────────────────────────────────────────
        if (!$this->option('skip-files')) {
            $this->newLine();
            $this->info('Extracting uploaded files…');
            $this->extractFiles($zip);
        }

        $zip->close();

        $this->newLine();
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function resetSequences(array $tables): void
    {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) {
                continue;
            }

            try {
                $seq = DB::selectOne(
                    "SELECT pg_get_serial_sequence(?, 'id') AS seq",
                    [$table]
                );

                if (!$seq || !$seq->seq) {
                    continue;
                }

                $max = DB::table($table)->max('id') ?? 0;
                DB::statement("SELECT setval(?, ?)", [$seq->seq, max((int) $max, 1)]);
                $this->line("  ✓ Reset sequence: {$table} → {$max}");
            } catch (\Throwable) {
                // Table uses UUID or non-serial PK — nothing to reset
            }
        }
    }

    private function extractFiles(ZipArchive $zip): void
    {
        $publicPath = storage_path('app/public');
        $prefix     = 'files/public/';
        $extracted  = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if (!str_starts_with($entry, $prefix)) {
                continue;
            }

            $relative = substr($entry, strlen($prefix));

            if (str_ends_with($entry, '/') || $relative === '') {
                continue;
            }

            $targetPath = $publicPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $targetDir  = dirname($targetPath);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            file_put_contents($targetPath, $zip->getFromIndex($i));
            $extracted++;
        }

        $this->line("  ✓ {$extracted} files extracted to storage/app/public/");
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}
