<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class DatabaseExportService
{
    // Tables that hold ephemeral, security-sensitive, environment-specific, or
    // driver-internal state that must not be migrated to a new server.
    private const EXCLUDED_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'failed_jobs',
        'job_batches',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'password_reset_tokens',
        'personal_access_tokens',
        'sqlite_sequence',   // SQLite internal sequence tracker
    ];

    private const CHUNK_SIZE = 500;

    /** Return every non-excluded table name in the database, alphabetically. */
    public function getExportableTables(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name AS table_name FROM sqlite_master WHERE type = 'table' ORDER BY name");
        } else {
            $rows = DB::select("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = 'public'
                  AND table_type   = 'BASE TABLE'
                ORDER BY table_name
            ");
        }

        return array_values(array_filter(
            array_column($rows, 'table_name'),
            fn($t) => !in_array($t, self::EXCLUDED_TABLES, true)
        ));
    }

    /** Return row counts for the given tables. */
    public function getTableRowCounts(array $tables): array
    {
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        return $counts;
    }

    /** Return the total byte size of all files under storage/app/public/. */
    public function getStorageFileSize(): int
    {
        $total = 0;
        $publicPath = storage_path('app/public');

        if (!is_dir($publicPath)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($publicPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $total += $file->getSize();
            }
        }

        return $total;
    }

    /**
     * Build the export ZIP at the given path.
     *
     * The ZIP contains:
     *   metadata.json
     *   tables/{table}.json   — one file per exportable table
     *   files/public/**       — mirror of storage/app/public/
     *
     * @param  callable|null  $onProgress  fn(string $step, int $count)
     */
    public function buildExportZip(string $zipPath, ?callable $onProgress = null): array
    {
        $tables    = $this->getExportableTables();
        $rowCounts = $this->getTableRowCounts($tables);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create ZIP file at {$zipPath}");
        }

        // ── Tables ────────────────────────────────────────────────────────────
        foreach ($tables as $table) {
            $json = $this->exportTable($table);
            $zip->addFromString("tables/{$table}.json", $json);

            if ($onProgress) {
                $onProgress($table, $rowCounts[$table]);
            }
        }

        // ── Uploaded files ────────────────────────────────────────────────────
        $filesStats = $this->addStorageFilesToZip($zip);

        if ($onProgress) {
            $onProgress('files', $filesStats['total_files']);
        }

        // ── Metadata ──────────────────────────────────────────────────────────
        $metadata = json_encode([
            'app_version'     => config('app.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'exported_at'     => now()->toIso8601String(),
            'tables'          => $tables,
            'row_counts'      => $rowCounts,
            'files'           => $filesStats,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $zip->addFromString('metadata.json', $metadata);
        $zip->close();

        return [
            'tables'    => $tables,
            'row_counts' => $rowCounts,
            'files'     => $filesStats,
        ];
    }

    /** Ensure the exports directory exists and return its path. */
    public function getExportDirectory(): string
    {
        $dir = storage_path('app/exports');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /** Delete any export ZIPs older than $hoursOld hours. */
    public function cleanOldExports(int $hoursOld = 24): void
    {
        $dir = storage_path('app/exports');

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zip') as $file) {
            if (filemtime($file) < time() - ($hoursOld * 3600)) {
                @unlink($file);
            }
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function exportTable(string $table): string
    {
        $rows  = [];
        $hasId = Schema::hasColumn($table, 'id');

        if ($hasId) {
            // chunk() requires an orderBy; use id for large tables to keep memory bounded.
            DB::table($table)->orderBy('id')->chunk(self::CHUNK_SIZE, function ($chunk) use (&$rows) {
                foreach ($chunk as $row) {
                    $rows[] = (array) $row;
                }
            });
        } else {
            // Pivot / lookup tables without a serial id are typically small — load directly.
            $rows = array_map(fn($r) => (array) $r, DB::table($table)->get()->all());
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function addStorageFilesToZip(ZipArchive $zip): array
    {
        $receiptsCount = 0;
        $logosCount    = 0;
        $totalFiles    = 0;
        $totalBytes    = 0;

        $publicPath = storage_path('app/public');

        if (!is_dir($publicPath)) {
            return [
                'receipts_count'        => 0,
                'logos_count'           => 0,
                'total_files'           => 0,
                'total_file_size_bytes' => 0,
            ];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($publicPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $realPath = $file->getPathname();
            $relative = 'files/public/' . str_replace('\\', '/', substr($realPath, strlen($publicPath) + 1));

            $zip->addFile($realPath, $relative);

            $totalBytes += $file->getSize();
            $totalFiles++;

            if (str_contains($relative, '/receipts/')) {
                $receiptsCount++;
            } elseif (str_contains($relative, '/logos/')) {
                $logosCount++;
            }
        }

        return [
            'receipts_count'        => $receiptsCount,
            'logos_count'           => $logosCount,
            'total_files'           => $totalFiles,
            'total_file_size_bytes' => $totalBytes,
        ];
    }
}
